<?php

$root = dirname(__DIR__);
$manifestPath = $root . '/bootstrap/cache/blade-icons.php';

if (! is_file($manifestPath)) {
    fwrite(STDERR, "Missing icon manifest. Run: php artisan icons:cache\n");
    exit(1);
}

$manifest = require $manifestPath;
$available = $manifest['lucide'][array_key_first($manifest['lucide'])] ?? [];

$used = [];
$errors = [];
$viewsDir = $root . '/resources/views';
$skipSvgFiles = ['components/application-logo.blade.php'];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($it as $file) {
    if (! str_ends_with($file->getPathname(), '.blade.php')) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsDir) + 1));
    $content = file_get_contents($file->getPathname());

    preg_match_all('/x-lucide-([a-z0-9-]+)/', $content, $matches);
    foreach ($matches[1] as $name) {
        $used[$name] = ($used[$name] ?? 0) + 1;
    }

    if (! in_array($relative, $skipSvgFiles, true) && preg_match('/<svg\b/', $content)) {
        $errors[] = "Inline <svg> remains in {$relative}";
    }

    if (preg_match('/<x-lucide-[^>]+\s:class=/', $content)) {
        $errors[] = "Blade :class on Lucide component in {$relative} (use x-bind:class for Alpine)";
    }

    if (preg_match('/createElementNS\(\s*[\'"]http:\/\/www\.w3\.org\/2000\/svg[\'"]/', $content)) {
        $errors[] = "JS createElementNS SVG in {$relative} (prefer <template> + cloneNode)";
    }
}

$missing = array_diff(array_keys($used), $available);

echo 'Used icons: '.count($used).PHP_EOL;
echo 'Registered icons: '.count($available).PHP_EOL;

if ($missing) {
    foreach ($missing as $name) {
        $errors[] = "Unknown icon: lucide-{$name} ({$used[$name]} uses)";
    }
}

if ($errors) {
    echo "\nIssues found:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}

echo "All checks passed.\n";
