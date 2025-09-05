<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class CreateEncryptedBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:encrypted 
                            {--disk=encrypted : The disk to store the backup}
                            {--compress : Compress the backup files}
                            {--retention=30 : Number of days to keep backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an encrypted backup of the application data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting encrypted backup process...');

        $disk = $this->option('disk');
        $compress = $this->option('compress');
        $retention = (int) $this->option('retention');

        try {
            // Create backup directory
            $backupDir = 'backups/' . now()->format('Y-m-d_H-i-s');
            $tempDir = storage_path('app/temp_backup_' . uniqid());

            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Backup database
            $this->backupDatabase($tempDir);

            // Backup files
            $this->backupFiles($tempDir);

            // Backup configuration
            $this->backupConfiguration($tempDir);

            // Create encrypted archive
            $backupPath = $this->createEncryptedArchive($tempDir, $backupDir, $compress);

            // Clean up temp directory
            File::deleteDirectory($tempDir);

            // Clean up old backups
            $this->cleanupOldBackups($retention);

            $this->info("Backup created successfully: {$backupPath}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            
            // Clean up temp directory on failure
            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            return Command::FAILURE;
        }
    }

    /**
     * Backup the database.
     */
    protected function backupDatabase(string $tempDir): void
    {
        $this->info('Backing up database...');

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");

        $filename = "database_{$connection}.sql";
        $filepath = "{$tempDir}/{$filename}";

        if ($connection === 'sqlite') {
            $databasePath = database_path($database);
            if (File::exists($databasePath)) {
                File::copy($databasePath, $filepath);
            }
        } else {
            // For MySQL/PostgreSQL, you would use mysqldump or pg_dump
            // This is a simplified version - in production, use proper database tools
            $this->warn('Database backup for ' . $connection . ' requires manual implementation');
        }
    }

    /**
     * Backup application files.
     */
    protected function backupFiles(string $tempDir): void
    {
        $this->info('Backing up application files...');

        $filesDir = "{$tempDir}/files";
        File::makeDirectory($filesDir, 0755, true);

        // Backup storage/app directory
        $storagePath = storage_path('app');
        if (File::exists($storagePath)) {
            File::copyDirectory($storagePath, "{$filesDir}/storage");
        }

        // Backup public/uploads if it exists
        $publicUploadsPath = public_path('uploads');
        if (File::exists($publicUploadsPath)) {
            File::copyDirectory($publicUploadsPath, "{$filesDir}/public_uploads");
        }
    }

    /**
     * Backup configuration files.
     */
    protected function backupConfiguration(string $tempDir): void
    {
        $this->info('Backing up configuration...');

        $configDir = "{$tempDir}/config";
        File::makeDirectory($configDir, 0755, true);

        // Backup .env file (encrypted)
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $envContent = File::get($envPath);
            $encryptedEnv = Crypt::encrypt($envContent);
            File::put("{$configDir}/.env.encrypted", $encryptedEnv);
        }

        // Backup config directory
        $configPath = config_path();
        if (File::exists($configPath)) {
            File::copyDirectory($configPath, "{$configDir}/app_config");
        }
    }

    /**
     * Create encrypted archive.
     */
    protected function createEncryptedArchive(string $tempDir, string $backupDir, bool $compress): string
    {
        $this->info('Creating encrypted archive...');

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . ($compress ? '.zip' : '.tar');
        $backupPath = "{$backupDir}/{$filename}";

        if ($compress) {
            $this->createZipArchive($tempDir, $backupPath);
        } else {
            $this->createTarArchive($tempDir, $backupPath);
        }

        // Encrypt the archive
        $this->encryptArchive($backupPath);

        return $backupPath;
    }

    /**
     * Create ZIP archive.
     */
    protected function createZipArchive(string $sourceDir, string $destinationPath): void
    {
        $zip = new ZipArchive();
        
        if ($zip->open($destinationPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create ZIP file: {$destinationPath}");
        }

        $this->addDirectoryToZip($zip, $sourceDir, '');
        $zip->close();
    }

    /**
     * Add directory to ZIP recursively.
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $basePath): void
    {
        $files = File::allFiles($sourceDir);
        
        foreach ($files as $file) {
            $relativePath = $basePath . $file->getRelativePathname();
            $zip->addFile($file->getPathname(), $relativePath);
        }
    }

    /**
     * Create TAR archive (simplified - in production use proper TAR library).
     */
    protected function createTarArchive(string $sourceDir, string $destinationPath): void
    {
        // This is a simplified implementation
        // In production, use a proper TAR library like phar or tar command
        $this->warn('TAR archive creation requires proper implementation');
    }

    /**
     * Encrypt the archive file.
     */
    protected function encryptArchive(string $filePath): void
    {
        $this->info('Encrypting backup archive...');

        $content = File::get($filePath);
        $encryptedContent = Crypt::encrypt($content);
        
        // Save encrypted content
        File::put($filePath . '.encrypted', $encryptedContent);
        
        // Remove original file
        File::delete($filePath);
        
        // Rename encrypted file
        File::move($filePath . '.encrypted', $filePath);
    }

    /**
     * Clean up old backups.
     */
    protected function cleanupOldBackups(int $retentionDays): void
    {
        $this->info("Cleaning up backups older than {$retentionDays} days...");

        $backupsDir = 'backups';
        $cutoffDate = now()->subDays($retentionDays);

        $files = Storage::disk('encrypted')->files($backupsDir);
        
        foreach ($files as $file) {
            $lastModified = Storage::disk('encrypted')->lastModified($file);
            $fileDate = \Carbon\Carbon::createFromTimestamp($lastModified);
            
            if ($fileDate->lt($cutoffDate)) {
                Storage::disk('encrypted')->delete($file);
                $this->line("Deleted old backup: {$file}");
            }
        }
    }
}
