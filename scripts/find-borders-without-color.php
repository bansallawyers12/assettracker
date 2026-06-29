<?php

$dirs = ['resources/views', 'resources/js', 'resources/css'];
$colorPattern = '/border-(?:transparent|black|white|gray|slate|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|current|inherit)/';
$widthPattern = '/\bborder(?:-(?:t|r|b|l|x|y|s|e))?(?:-\d+)?\b/';

$hits = [];

foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        continue;
    }

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());
        if (! preg_match('/\.(blade\.php|php|js|css)$/', $path)) {
            continue;
        }

        $lines = file($path);
        foreach ($lines as $num => $line) {
            if (! preg_match_all('/class="([^"]*)"|className\s*=\s*[\'"]([^\'"]+)[\'"]|className\s*=\s*`([^`]+)`|@apply\s+([^;"]+)|\'[^\']*\bborder\b[^\']*\'|"[^"]*\bborder\b[^"]*"/', $line, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                $classString = $match[1] ?: ($match[2] ?: ($match[3] ?: ($match[4] ?? '')));
                if ($classString === '' && isset($match[0])) {
                    $classString = trim($match[0], "'\"");
                }
                if (! preg_match($widthPattern, $classString)) {
                    continue;
                }
                if (preg_match($colorPattern, $classString)) {
                    continue;
                }
                if (str_contains($classString, 'border-collapse')) {
                    continue;
                }
                if (preg_match('/\b(?:file|print|hover|focus|dark|sm|md|lg|xl):border-(?:transparent|\d+|0|gray|red|blue|green|indigo|amber|orange|yellow|slate)/', $classString)) {
                    continue;
                }

                $hits[] = [$path, $num + 1, trim($line), $classString];
            }
        }
    }
}

foreach ($hits as [$path, $lineNo, $line, $cls]) {
    echo "$path:$lineNo: $cls\n";
}
echo "\n".count($hits)." hits\n";
