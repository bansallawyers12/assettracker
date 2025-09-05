<?php

namespace App\Providers;

use App\Filesystem\EncryptedFilesystemAdapter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

class EncryptedFilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Storage::extend('encrypted', function ($app, $config) {
            $adapter = new EncryptedFilesystemAdapter(
                $config['root'],
                $config['key'],
                $config
            );

            return new Filesystem($adapter);
        });
    }
}
