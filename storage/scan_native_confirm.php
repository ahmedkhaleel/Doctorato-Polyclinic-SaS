<?php
// Scan ALL panels for native window.confirm / alert / prompt usage (jarring,
// blocking, unstyled) — the app has a useConfirm() system that should be used
// instead. Reports file:line + the offending call.
$root = __DIR__ . '/../resources/js/Pages';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$hits = [];
foreach ($it as $f) {
    if (substr($f, -4) !== '.vue') {
        continue;
    }
    foreach (file($f) as $i => $line) {
        // match window.confirm( / confirm( / alert( / prompt( as a call,
        // but NOT useConfirm, confirmText, confirmed, .confirm( method chains, etc.
        if (preg_match('/(?<![\w.])(window\.)?(confirm|alert|prompt)\s*\(/', $line, $m)) {
            // exclude obvious false positives
            $whole = trim($line);
            if (preg_match('/useConfirm|confirmText|confirm:|confirmColor|confirmLabel|\bconfirmed\b|confirmDelete\s*\(|confirm\(\)|@confirm|:confirm/', $whole)) {
                // still flag window.confirm explicitly even if "confirm" appears elsewhere
                if (! preg_match('/(?<![\w.])(window\.)?(confirm|alert|prompt)\s*\(\s*[\'"`]/', $whole)) {
                    continue;
                }
            }
            $top = explode('/', str_replace($root.'/', '', (string) $f))[0];
            $hits[] = [$top, str_replace($root.'/', '', (string) $f).':'.($i + 1), $whole];
        }
    }
}

$byTop = [];
foreach ($hits as [$t, $loc, $code]) {
    $byTop[$t] = ($byTop[$t] ?? 0) + 1;
}
echo 'Native confirm/alert/prompt calls: ' . count($hits) . "\n";
ksort($byTop);
foreach ($byTop as $t => $n) {
    echo "  $t: $n\n";
}
echo "\n--- detail ---\n";
foreach ($hits as [$t, $loc, $code]) {
    echo "  $loc\n      ".substr($code, 0, 110)."\n";
}
