<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = DB::table('users')
    ->whereNull('email_verified_at')
    ->update(['email_verified_at' => now()]);

echo "Marked {$count} user(s) as verified.\n";
