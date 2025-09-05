<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use ZipArchive;

class RestoreEncryptedBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore 
                            {backup_file : The backup file to restore}
                            {--disk=encrypted : The disk where the backup is stored}
                            {--force : Force restoration without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore an encrypted backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backupFile = $this->argument('backup_file');
        $disk = $this->option('disk');
        $force = $this->option('force');

        if (!$force && !$this->confirm('This will overwrite current data. Are you sure?')) {
            $this->info('Restoration cancelled.');
            return Command::SUCCESS;
        }

        try {
            $this->info("Starting restoration from: {$backupFile}");

            // Check if backup file exists
            if (!Storage::disk($disk)->exists($backupFile)) {
                $this->error("Backup file not found: {$backupFile}");
                return Command::FAILURE;
            }

            // Create temporary directory
            $tempDir = storage_path('app/temp_restore_' . uniqid());
            File::makeDirectory($tempDir, 0755, true);

            // Download and decrypt backup
            $this->decryptAndExtractBackup($backupFile, $tempDir, $disk);

            // Restore database
            $this->restoreDatabase($tempDir);

            // Restore files
            $this->restoreFiles($tempDir);

            // Restore configuration
            $this->restoreConfiguration($tempDir);

            // Clean up
            File::deleteDirectory($tempDir);

            $this->info('Backup restored successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Restoration failed: " . $e->getMessage());
            
            // Clean up temp directory on failure
            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            return Command::FAILURE;
        }
    }

    /**
     * Decrypt and extract backup file.
     */
    protected function decryptAndExtractBackup(string $backupFile, string $tempDir, string $disk): void
    {
        $this->info('Decrypting and extracting backup...');

        // Download encrypted backup
        $encryptedContent = Storage::disk($disk)->get($backupFile);
        
        // Decrypt content
        $decryptedContent = Crypt::decrypt($encryptedContent);
        
        // Save decrypted content temporarily
        $tempBackupFile = $tempDir . '/backup_decrypted.zip';
        File::put($tempBackupFile, $decryptedContent);

        // Extract ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($tempBackupFile) !== TRUE) {
            throw new \Exception("Cannot open backup file: {$tempBackupFile}");
        }

        $zip->extractTo($tempDir);
        $zip->close();

        // Remove temporary backup file
        File::delete($tempBackupFile);
    }

    /**
     * Restore database.
     */
    protected function restoreDatabase(string $tempDir): void
    {
        $this->info('Restoring database...');

        $databaseFiles = File::glob("{$tempDir}/database_*.sql");
        
        foreach ($databaseFiles as $dbFile) {
            $connection = basename($dbFile, '.sql');
            $connection = str_replace('database_', '', $connection);
            
            $this->restoreDatabaseFile($dbFile, $connection);
        }
    }

    /**
     * Restore specific database file.
     */
    protected function restoreDatabaseFile(string $dbFile, string $connection): void
    {
        $this->line("Restoring database for connection: {$connection}");

        if ($connection === 'sqlite') {
            $databasePath = database_path(config("database.connections.{$connection}.database"));
            File::copy($dbFile, $databasePath);
        } else {
            // For MySQL/PostgreSQL, implement proper restoration
            $this->warn("Database restoration for {$connection} requires manual implementation");
        }
    }

    /**
     * Restore application files.
     */
    protected function restoreFiles(string $tempDir): void
    {
        $this->info('Restoring application files...');

        $filesDir = "{$tempDir}/files";

        if (File::exists("{$filesDir}/storage")) {
            $this->line('Restoring storage files...');
            File::copyDirectory("{$filesDir}/storage", storage_path('app'));
        }

        if (File::exists("{$filesDir}/public_uploads")) {
            $this->line('Restoring public uploads...');
            $publicUploadsPath = public_path('uploads');
            if (!File::exists($publicUploadsPath)) {
                File::makeDirectory($publicUploadsPath, 0755, true);
            }
            File::copyDirectory("{$filesDir}/public_uploads", $publicUploadsPath);
        }
    }

    /**
     * Restore configuration files.
     */
    protected function restoreConfiguration(string $tempDir): void
    {
        $this->info('Restoring configuration...');

        $configDir = "{$tempDir}/config";

        // Restore .env file
        $envFile = "{$configDir}/.env.encrypted";
        if (File::exists($envFile)) {
            $this->line('Restoring .env file...');
            $encryptedEnv = File::get($envFile);
            $decryptedEnv = Crypt::decrypt($encryptedEnv);
            File::put(base_path('.env'), $decryptedEnv);
        }

        // Restore config directory
        if (File::exists("{$configDir}/app_config")) {
            $this->line('Restoring application configuration...');
            File::copyDirectory("{$configDir}/app_config", config_path());
        }
    }
}
