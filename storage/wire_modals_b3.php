<?php
// Wire v-focus-trap into the remaining button-only-close modals. Derives a
// safe closer from the v-if condition. Line-targeted; only touches a <div>
// that has both the v-if and `fixed inset-0` and no existing v-focus-trap.
$root = __DIR__ . '/../resources/js/Pages/Admin';
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if (substr($f, -4) === '.vue') {
        $files[] = (string) $f;
    }
}
sort($files);

function deriveCloser(string $cond): ?string
{
    $cond = trim($cond);
    // compound: close the first identifier (boolean gate) → false
    if (strpos($cond, '&&') !== false) {
        $first = trim(explode('&&', $cond)[0]);
        if (! preg_match('/^[A-Za-z_$][\w.$]*$/', $first)) {
            return null;
        }

        return '() => ('.$first.' = false)';
    }
    // single identifier only (no operators/brackets)
    if (! preg_match('/^[A-Za-z_$][\w.$]*$/', $cond)) {
        return null;
    }
    // object/id refs close to null; boolean show-flags close to false
    if (preg_match('/^(editing|updating|selected)/', $cond)) {
        return '() => ('.$cond.' = null)';
    }

    return '() => ('.$cond.' = false)';
}

$done = 0;
$skip = [];
foreach ($files as $file) {
    $lines = file($file);
    $changed = false;
    foreach ($lines as $idx => $line) {
        if (strpos($line, 'fixed inset-0') === false) {
            continue;
        }
        if (strpos($line, 'v-focus-trap') !== false) {
            continue;
        }
        if (! preg_match('/<div\s+v-if="([^"]+)"/', $line, $m)) {
            continue;
        }
        $cond = $m[1];
        $closer = deriveCloser($cond);
        if ($closer === null) {
            $skip[] = str_replace($root.'/', '', $file)." [$cond]";

            continue;
        }
        $needle = 'v-if="'.$cond.'"';
        $inject = $needle.' v-focus-trap="'.$closer.'" role="dialog" aria-modal="true"';
        $lines[$idx] = str_replace($needle, $inject, $line);
        $changed = true;
        $done++;
        echo 'OK   '.str_replace($root.'/', '', $file)."  v-if=[$cond]  closer=[$closer]\n";
    }
    if ($changed) {
        file_put_contents($file, implode('', $lines));
    }
}
echo "\nWired: $done\n";
if ($skip) {
    echo "Skipped (unparseable cond): \n  ".implode("\n  ", $skip)."\n";
}
