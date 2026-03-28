#!/usr/bin/env php
<?php

/**
 * Standalone import: no Excel on the server. Edit scripts/companies_data.php then run:
 *
 *   php scripts/import_companies.php --user=3
 *
 * From project root (same folder as artisan). Options:
 *   --user=ID     (required) users.id that will own the entities
 *   --dry-run     Parse only, no DB writes
 *   --skip-directors   No Person / EntityPerson rows
 */

use App\Models\User;
use App\Services\CompanyRegisterImporter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

$basePath = dirname(__DIR__);

if (! is_readable($basePath.'/vendor/autoload.php')) {
    fwrite(STDERR, "Run this from the project root (vendor/ missing in {$basePath}).\n");
    exit(1);
}

require $basePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$opts = getopt('', ['user:', 'dry-run', 'skip-directors']);

$userId = isset($opts['user']) ? (int) $opts['user'] : 0;
if ($userId < 1) {
    fwrite(STDERR, "Required: --user=YOUR_USER_ID (see users table).\n");
    exit(1);
}

if (! User::query()->whereKey($userId)->exists()) {
    fwrite(STDERR, "No user with id {$userId}.\n");
    exit(1);
}

$dataFile = $basePath.'/scripts/companies_data.php';
if (! is_readable($dataFile)) {
    fwrite(STDERR, "Missing: {$dataFile}\n");
    exit(1);
}

$rows = require $dataFile;
if (! is_array($rows)) {
    fwrite(STDERR, "companies_data.php must return an array of rows.\n");
    exit(1);
}

$dryRun = array_key_exists('dry-run', $opts);
$skipDirectors = array_key_exists('skip-directors', $opts);

$result = $app->make(CompanyRegisterImporter::class)->importFromRows(
    $rows,
    $userId,
    $dryRun,
    $skipDirectors
);

foreach ($result['warnings'] as $w) {
    fwrite(STDERR, $w."\n");
}

echo $dryRun ? "[dry-run]\n" : "Done.\n";
echo "Created: {$result['created']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}, Director links: {$result['directors_linked']}\n";
exit(0);
