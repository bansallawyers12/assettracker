<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CompanyRegisterImporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportCompanyDetailsSpreadsheet extends Command
{
    protected $signature = 'business-entities:import-company-details
                            {--file= : Path to .xlsx (default: public/Companydetails.xlsx)}
                            {--user= : User ID that will own all imported entities (required)}
                            {--dry-run : Parse and report only; no database writes}
                            {--skip-directors : Do not create Person / EntityPerson rows}';

    protected $description = 'Import company rows from Companydetails-style spreadsheet into business_entities';

    public function handle(CompanyRegisterImporter $importer): int
    {
        $userId = $this->option('user');
        if ($userId === null || $userId === '') {
            $this->error('Required: --user=<id> (the account that should own these entities).');

            return self::FAILURE;
        }
        $userId = (int) $userId;
        if (! User::query()->whereKey($userId)->exists()) {
            $this->error("No user found with id {$userId}.");

            return self::FAILURE;
        }

        $path = $this->option('file') ?: public_path('Companydetails.xlsx');
        if (! is_readable($path)) {
            $this->error("File not readable: {$path}");

            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestDataRow('D');
        $rows = [];

        for ($row = 3; $row <= $highestRow; $row++) {
            $legalName = trim((string) $this->cellString($sheet, 'D', $row));
            if ($legalName === '') {
                continue;
            }
            $rows[] = [
                'legal_name' => $legalName,
                'abn' => $this->cellRaw($sheet, 'B', $row),
                'acn' => $this->cellRaw($sheet, 'C', $row),
                'under_trust_of' => trim((string) $this->cellString($sheet, 'E', $row)),
                'classification' => trim((string) $this->cellString($sheet, 'F', $row)),
                'director_name' => trim((string) $this->cellString($sheet, 'G', $row)),
                'address' => trim((string) $this->cellString($sheet, 'H', $row)),
                'asic_renewal' => $this->parseExcelDate($sheet, 'I', $row),
            ];
        }

        $dryRun = (bool) $this->option('dry-run');
        $skipDirectors = (bool) $this->option('skip-directors');

        $result = $importer->importFromRows($rows, $userId, $dryRun, $skipDirectors);

        foreach ($result['warnings'] as $w) {
            $this->warn($w);
        }

        $this->info($dryRun ? '[dry-run] rows that would be created / updated:' : 'Import finished.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $result['created']],
                ['Updated', $result['updated']],
                ['Skipped (other owner)', $result['skipped']],
                ['Director links added', $result['directors_linked']],
            ]
        );

        return self::SUCCESS;
    }

    private function cellString($sheet, string $col, int $row): string
    {
        $value = $sheet->getCell($col.$row)->getValue();

        return $value === null ? '' : trim((string) $value);
    }

    private function cellRaw($sheet, string $col, int $row): mixed
    {
        return $sheet->getCell($col.$row)->getValue();
    }

    private function parseExcelDate($sheet, string $col, int $row): ?Carbon
    {
        $value = $sheet->getCell($col.$row)->getValue();
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);

                return Carbon::instance($dt);
            } catch (\Throwable) {
                return null;
            }
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
