<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Imports business entities from an Excel file by delegating to
 * `php artisan business-entities:import-company-details`.
 *
 * Setup:
 * 1. Copy Companydetails.xlsx to database/data/Companydetails.xlsx (or set COMPANY_IMPORT_FILE).
 * 2. In .env: COMPANY_IMPORT_USER_ID=3  (the owning users.id).
 * 3. Run: php artisan db:seed --class=CompanyDetailsSeeder
 *
 * This is not included in DatabaseSeeder by default — run it only when you intend to import.
 */
class CompanyDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $userId = (int) env('COMPANY_IMPORT_USER_ID', 0);
        if ($userId < 1) {
            $this->warn('Skipped: set COMPANY_IMPORT_USER_ID in .env to a valid users.id.');

            return;
        }

        $file = env('COMPANY_IMPORT_FILE', database_path('data/Companydetails.xlsx'));
        if (! is_readable($file)) {
            $this->warn("Skipped: file not readable: {$file}");
            $this->warn('Copy Companydetails.xlsx to database/data/ or set COMPANY_IMPORT_FILE to a full path.');

            return;
        }

        $params = [
            '--user' => (string) $userId,
            '--file' => $file,
        ];

        if (filter_var(env('COMPANY_IMPORT_DRY_RUN', false), FILTER_VALIDATE_BOOLEAN)) {
            $params['--dry-run'] = true;
        }

        if (filter_var(env('COMPANY_IMPORT_SKIP_DIRECTORS', false), FILTER_VALIDATE_BOOLEAN)) {
            $params['--skip-directors'] = true;
        }

        $exit = Artisan::call('business-entities:import-company-details', $params);

        $this->line(trim(Artisan::output()));

        if ($exit !== 0) {
            throw new \RuntimeException('Company details import command exited with code '.$exit.'.');
        }
    }
}
