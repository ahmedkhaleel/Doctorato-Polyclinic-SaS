<?php
// Audit Doctor/Secretary/Patient panels for two UX gaps already fixed in Admin:
//  A) forms that submit via useForm but never display validation errors
//  B) icon-only buttons with no accessible name (aria-label/title)
$root = __DIR__ . '/../resources/js/Pages';
$panels = ['Doctor', 'Secretary', 'Patient'];

$files = [];
foreach ($panels as $p) {
    $dir = "$root/$p";
    if (! is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $f) {
        if (substr($f, -4) === '.vue') {
            $files[] = (string) $f;
        }
    }
}
sort($files);

$noErrors = [];
$iconBtns = [];
foreach ($files as $file) {
    $src = file_get_contents($file);
    $rel = str_replace($root . '/', '', $file);

    // A) silent forms
    if (preg_match('/useForm\b/', $src) && preg_match('/\.(post|put|patch|delete|submit)\s*\(/', $src)) {
        $shows = preg_match('/\.errors\b/', $src) || preg_match('/\berrors\./', $src) || preg_match('/hasErrors/', $src)
            || preg_match('/InputError|FormError|ErrorMessage|FormErrors|error-message/i', $src) || preg_match('/v-if="[^"]*error/i', $src);
        if (! $shows) {
            $noErrors[] = $rel;
        }
    }

    // B) unlabeled icon-only buttons
    if (preg_match_all('/<button\b([^>]*?)>(.*?)<\/button>/s', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $mm) {
            $attrs = $mm[1];
            $inner = $mm[2];
            if (strpos($inner, '<svg') === false) {
                continue;
            }
            if (trim(preg_replace('/<[^>]+>/', '', $inner)) !== '') {
                continue;
            }
            if (preg_match('/(?:^|\s)(title|aria-label|:title|:aria-label|v-tooltip)=/', $attrs)) {
                continue;
            }
            $iconBtns[$rel] = ($iconBtns[$rel] ?? 0) + 1;
        }
    }
}

echo 'A) Forms with NO error display: ' . count($noErrors) . "\n";
foreach ($noErrors as $f) {
    echo "   $f\n";
}
echo "\nB) Files with unlabeled icon-only buttons: " . count($iconBtns) . ' (total buttons: ' . array_sum($iconBtns) . ")\n";
arsort($iconBtns);
foreach ($iconBtns as $f => $n) {
    echo "   $n  $f\n";
}
