<?php
// Find modal overlay divs in admin pages and classify them for v-focus-trap wiring.
//   AUTO  = overlay <div v-if="REF" ... @click.self="REF = false"> not yet wired
//   WIRED = already has v-focus-trap
//   MANUAL= modal-like overlay whose close pattern isn't the standard REF=false
$root = __DIR__ . '/../resources/js/Pages/Admin';
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if (substr($f, -4) === '.vue') {
        $files[] = (string) $f;
    }
}
sort($files);

$auto = [];
$wired = 0;
$manual = [];

foreach ($files as $file) {
    foreach (file($file) as $i => $line) {
        // candidate modal overlay: a div with v-if and fixed inset-0 + z-index
        if (! preg_match('/<div\s+v-if="([^"]+)"[^>]*\bfixed inset-0\b/', $line, $m)) {
            continue;
        }
        if (strpos($line, 'z-') === false && strpos($line, 'inset-0') === false) {
            continue;
        }
        $rel = str_replace($root . '/', '', $file);
        $ln = $i + 1;

        if (strpos($line, 'v-focus-trap') !== false) {
            $wired++;

            continue;
        }
        $cond = trim($m[1]);
        // standard closeable: @click.self="<cond> = false" where cond is a plain ref
        if (preg_match('/@click\.self="\s*'.preg_quote($cond, '/').'\s*=\s*false\s*"/', $line)
            && preg_match('/^[A-Za-z_$][\w.$]*$/', $cond)) {
            $auto[] = [$rel, $ln, $cond];
        } else {
            // capture how it closes for manual review
            $self = '';
            if (preg_match('/@click\.self="([^"]*)"/', $line, $sm)) {
                $self = $sm[1];
            }
            $manual[] = [$rel, $ln, $cond, $self];
        }
    }
}

echo 'AUTO-wirable modal overlays: ' . count($auto) . "\n";
foreach ($auto as [$f, $l, $c]) {
    printf("  %-46s :%-4d  close=[%s]\n", $f, $l, $c);
}
echo "\nAlready wired: $wired\n";
echo "\nMANUAL (non-standard close): " . count($manual) . "\n";
foreach ($manual as [$f, $l, $c, $s]) {
    printf("  %-46s :%-4d  v-if=[%s] click.self=[%s]\n", $f, $l, $c, $s);
}
