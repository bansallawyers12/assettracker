<?php

declare(strict_types=1);

/**
 * PHP configuration (phpinfo) — gated by .env. Delete this file when you no longer need it.
 *
 * Usage: https://your-site/phpinfo.php?token=YOUR_PHPINFO_ACCESS_TOKEN
 *
 * 1. Add PHPINFO_ACCESS_TOKEN=your-long-random-secret to .env (never commit real values).
 * 2. Open the URL with matching ?token=
 * 3. Search the page for upload_max_filesize, post_max_size, memory_limit, etc.
 * 4. Remove public/phpinfo.php from production after troubleshooting (exposes server details).
 */

if (! is_file(__DIR__.'/../.env')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Server configuration unavailable (no .env).';
    exit;
}

require __DIR__.'/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$expected = $_ENV['PHPINFO_ACCESS_TOKEN'] ?? '';
$token = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';

if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden. Set PHPINFO_ACCESS_TOKEN in .env and open this URL with ?token= matching that value.';
    exit;
}

phpinfo();
