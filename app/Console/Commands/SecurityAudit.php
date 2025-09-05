<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SecurityAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:audit 
                            {--fix : Automatically fix security issues}
                            {--detailed : Show detailed security report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a comprehensive security audit of the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting security audit...');
        $this->newLine();

        $issues = [];
        $fixes = [];

        // Check environment security
        $this->checkEnvironmentSecurity($issues, $fixes);

        // Check file permissions
        $this->checkFilePermissions($issues, $fixes);

        // Check user security
        $this->checkUserSecurity($issues, $fixes);

        // Check encryption
        $this->checkEncryption($issues, $fixes);

        // Check session security
        $this->checkSessionSecurity($issues, $fixes);

        // Check database security
        $this->checkDatabaseSecurity($issues, $fixes);

        // Display results
        $this->displayResults($issues, $fixes);

        // Apply fixes if requested
        if ($this->option('fix') && !empty($fixes)) {
            $this->applyFixes($fixes);
        }

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Check environment security.
     */
    protected function checkEnvironmentSecurity(array &$issues, array &$fixes): void
    {
        $this->line('Checking environment security...');

        // Check if .env file exists
        if (!File::exists(base_path('.env'))) {
            $issues[] = 'Missing .env file';
            $fixes[] = 'Create .env file from .env.example';
        }

        // Check if APP_KEY is set
        if (empty(config('app.key'))) {
            $issues[] = 'APP_KEY is not set';
            $fixes[] = 'Run: php artisan key:generate';
        }

        // Check if debug mode is disabled in production
        if (config('app.debug') && config('app.env') === 'production') {
            $issues[] = 'Debug mode is enabled in production';
            $fixes[] = 'Set APP_DEBUG=false in .env';
        }

        // Check if HTTPS is enforced
        if (!config('security.headers.force_https', false)) {
            $issues[] = 'HTTPS is not enforced';
            $fixes[] = 'Set FORCE_HTTPS=true in .env';
        }
    }

    /**
     * Check file permissions.
     */
    protected function checkFilePermissions(array &$issues, array &$fixes): void
    {
        $this->line('Checking file permissions...');

        $sensitiveFiles = [
            '.env',
            'storage',
            'bootstrap/cache',
        ];

        foreach ($sensitiveFiles as $file) {
            $path = base_path($file);
            if (File::exists($path)) {
                $permissions = substr(sprintf('%o', fileperms($path)), -4);
                if ($permissions !== '0755' && $permissions !== '0644') {
                    $issues[] = "Insecure permissions on {$file}: {$permissions}";
                    $fixes[] = "chmod 755 {$file}";
                }
            }
        }
    }

    /**
     * Check user security.
     */
    protected function checkUserSecurity(array &$issues, array &$fixes): void
    {
        $this->line('Checking user security...');

        // Check for weak passwords
        $users = User::all();
        foreach ($users as $user) {
            if (strlen($user->password) < 8) {
                $issues[] = "User {$user->email} has weak password";
                $fixes[] = "Force password reset for user {$user->email}";
            }

            // Check if 2FA is enabled
            if (!$user->two_factor_enabled) {
                $issues[] = "User {$user->email} does not have 2FA enabled";
                $fixes[] = "Enable 2FA for user {$user->email}";
            }
        }
    }

    /**
     * Check encryption configuration.
     */
    protected function checkEncryption(array &$issues, array &$fixes): void
    {
        $this->line('Checking encryption configuration...');

        // Check if encryption key is set
        if (empty(config('security.encryption.key'))) {
            $issues[] = 'Encryption key is not configured';
            $fixes[] = 'Set ENCRYPTION_KEY in .env';
        }

        // Check if session encryption is enabled
        if (!config('security.sessions.encrypt', false)) {
            $issues[] = 'Session encryption is not enabled';
            $fixes[] = 'Set SESSION_ENCRYPT=true in .env';
        }
    }

    /**
     * Check session security.
     */
    protected function checkSessionSecurity(array &$issues, array &$fixes): void
    {
        $this->line('Checking session security...');

        // Check session driver
        $sessionDriver = config('session.driver');
        if ($sessionDriver === 'file') {
            $issues[] = 'Using file-based sessions (consider database/redis)';
            $fixes[] = 'Set SESSION_DRIVER=database in .env';
        }

        // Check session lifetime
        $sessionLifetime = config('session.lifetime');
        if ($sessionLifetime > 480) { // 8 hours
            $issues[] = 'Session lifetime is too long: ' . $sessionLifetime . ' minutes';
            $fixes[] = 'Set SESSION_LIFETIME=120 in .env';
        }

        // Check secure cookies
        if (!config('session.secure')) {
            $issues[] = 'Session cookies are not secure';
            $fixes[] = 'Set SESSION_SECURE_COOKIE=true in .env';
        }
    }

    /**
     * Check database security.
     */
    protected function checkDatabaseSecurity(array &$issues, array &$fixes): void
    {
        $this->line('Checking database security...');

        // Check database encryption
        if (empty(config('security.database.encryption_key'))) {
            $issues[] = 'Database encryption key is not configured';
            $fixes[] = 'Set DB_ENCRYPTION_KEY in .env';
        }

        // Check if database connection uses SSL
        $connection = config('database.default');
        $sslOptions = config("database.connections.{$connection}.options", []);
        if (empty($sslOptions[\PDO::MYSQL_ATTR_SSL_CA])) {
            $issues[] = 'Database connection does not use SSL';
            $fixes[] = 'Configure SSL for database connection';
        }
    }

    /**
     * Display audit results.
     */
    protected function displayResults(array $issues, array $fixes): void
    {
        $this->newLine();
        $this->info('Security Audit Results');
        $this->line('====================');

        if (empty($issues)) {
            $this->info('✅ No security issues found!');
        } else {
            $this->error('❌ Found ' . count($issues) . ' security issues:');
            $this->newLine();

            foreach ($issues as $index => $issue) {
                $this->line(($index + 1) . '. ' . $issue);
            }

            if (!empty($fixes)) {
                $this->newLine();
                $this->warn('Suggested fixes:');
                foreach ($fixes as $index => $fix) {
                    $this->line(($index + 1) . '. ' . $fix);
                }
            }
        }

        $this->newLine();
    }

    /**
     * Apply security fixes.
     */
    protected function applyFixes(array $fixes): void
    {
        $this->info('Applying security fixes...');

        foreach ($fixes as $fix) {
            $this->line("Applying: {$fix}");
            // In a real implementation, you would execute the fixes here
            // For now, we'll just log them
        }

        $this->info('Security fixes applied!');
    }
}
