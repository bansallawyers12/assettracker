<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SetupSecurity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:setup 
                            {--force : Force setup even if already configured}
                            {--skip-migration : Skip running migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up initial security configuration for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 Setting up security for Asset Tracker...');
        $this->newLine();

        try {
            // Check if already configured
            if (!$this->option('force') && $this->isAlreadyConfigured()) {
                $this->warn('Security appears to be already configured. Use --force to reconfigure.');
                return Command::SUCCESS;
            }

            // Generate keys
            $this->generateKeys();

            // Create encrypted storage directory
            $this->createEncryptedStorage();

            // Run migrations
            if (!$this->option('skip-migration')) {
                $this->runMigrations();
            }

            // Set file permissions
            $this->setFilePermissions();

            // Create .env.example
            $this->createEnvExample();

            // Run initial security audit
            $this->runSecurityAudit();

            $this->newLine();
            $this->info('✅ Security setup completed successfully!');
            $this->newLine();
            $this->warn('⚠️  Important: Please review and update your .env file with the generated keys.');
            $this->warn('⚠️  Make sure to enable HTTPS in production.');
            $this->warn('⚠️  Run "php artisan security:audit" to verify your configuration.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Security setup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Check if security is already configured.
     */
    protected function isAlreadyConfigured(): bool
    {
        return !empty(config('app.key')) && 
               File::exists(storage_path('app/encrypted'));
    }

    /**
     * Generate security keys.
     */
    protected function generateKeys(): void
    {
        $this->info('Generating security keys...');

        // Generate APP_KEY if not exists
        if (empty(config('app.key'))) {
            Artisan::call('key:generate');
            $this->line('✓ Generated APP_KEY');
        }

        // Generate additional encryption keys
        $keys = [
            'ENCRYPTION_KEY' => base64_encode(random_bytes(32)),
            'DB_ENCRYPTION_KEY' => base64_encode(random_bytes(32)),
            'BACKUP_ENCRYPTION_KEY' => base64_encode(random_bytes(32)),
        ];

        $envPath = base_path('.env');
        $envContent = File::exists($envPath) ? File::get($envPath) : '';

        foreach ($keys as $key => $value) {
            if (!Str::contains($envContent, $key)) {
                $envContent .= "\n{$key}={$value}";
                $this->line("✓ Generated {$key}");
            }
        }

        if (File::exists($envPath)) {
            File::put($envPath, $envContent);
        }

        $this->warn('⚠️  Please add these keys to your .env file:');
        foreach ($keys as $key => $value) {
            $this->line("{$key}={$value}");
        }
    }

    /**
     * Create encrypted storage directory.
     */
    protected function createEncryptedStorage(): void
    {
        $this->info('Creating encrypted storage directory...');

        $encryptedPath = storage_path('app/encrypted');
        if (!File::exists($encryptedPath)) {
            File::makeDirectory($encryptedPath, 0755, true);
            $this->line('✓ Created encrypted storage directory');
        }
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('Running database migrations...');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->line('✓ Database migrations completed');
        } catch (\Exception $e) {
            $this->warn('Migration failed: ' . $e->getMessage());
            $this->warn('Please run "php artisan migrate" manually.');
        }
    }

    /**
     * Set secure file permissions.
     */
    protected function setFilePermissions(): void
    {
        $this->info('Setting file permissions...');

        $directories = [
            storage_path(),
            storage_path('app'),
            storage_path('app/encrypted'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                chmod($dir, 0755);
                $this->line("✓ Set permissions for " . basename($dir));
            }
        }

        // Set .env file permissions
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            chmod($envPath, 0644);
            $this->line('✓ Set permissions for .env file');
        }
    }

    /**
     * Create .env.example file.
     */
    protected function createEnvExample(): void
    {
        $this->info('Creating .env.example file...');

        $envExample = base_path('.env.example');
        if (!File::exists($envExample)) {
            $content = $this->getEnvExampleContent();
            File::put($envExample, $content);
            $this->line('✓ Created .env.example file');
        }
    }

    /**
     * Get .env.example content.
     */
    protected function getEnvExampleContent(): string
    {
        return <<<'ENV'
APP_NAME="Asset Tracker"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=assettracker
DB_USERNAME=assettracker_user
DB_PASSWORD=

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Cache Configuration
CACHE_DRIVER=redis
CACHE_PREFIX=assettracker_

# Queue Configuration
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Security Headers
SECURE_HEADERS=true
FORCE_HTTPS=true

# File Encryption
FILESYSTEM_DISK=encrypted
ENCRYPTION_KEY=

# Backup Configuration
BACKUP_ENCRYPTION_KEY=
BACKUP_DISK=encrypted

# Two-Factor Authentication
TWO_FA_ISSUER="${APP_NAME}"

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15

# Security Features
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_SPECIAL_CHARS=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true

# Database Encryption
DB_ENCRYPTION_KEY=

# File Upload Security
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES="pdf,jpg,jpeg,png,doc,docx,xls,xlsx"
ENV;
    }

    /**
     * Run initial security audit.
     */
    protected function runSecurityAudit(): void
    {
        $this->info('Running initial security audit...');

        try {
            Artisan::call('security:audit');
            $this->line('✓ Security audit completed');
        } catch (\Exception $e) {
            $this->warn('Security audit failed: ' . $e->getMessage());
        }
    }
}
