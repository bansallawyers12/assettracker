<?php

namespace App\Console\Commands;

use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Note;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

    public function handle(): int
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
        $dryRun = (bool) $this->option('dry-run');
        $skipDirectors = (bool) $this->option('skip-directors');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $directorsLinked = 0;
        $warnings = [];

        $processRow = function (int $row) use (
            $sheet,
            $userId,
            $dryRun,
            $skipDirectors,
            &$created,
            &$updated,
            &$skipped,
            &$directorsLinked,
            &$warnings
        ): void {
            $legalName = trim((string) $this->cellString($sheet, 'D', $row));
            if ($legalName === '') {
                return;
            }

            $abn = $this->normalizeAbn($this->cellRaw($sheet, 'B', $row));
            $acn = $this->normalizeAcn($this->cellRaw($sheet, 'C', $row));
            $trustOf = trim((string) $this->cellString($sheet, 'E', $row));
            $statusLabel = trim((string) $this->cellString($sheet, 'F', $row));
            $directorName = trim((string) $this->cellString($sheet, 'G', $row));
            $address = trim((string) $this->cellString($sheet, 'H', $row));
            $asicRenewal = $this->parseExcelDate($sheet, 'I', $row);

            $entity = BusinessEntity::query()
                ->when($abn, fn ($q) => $q->where('abn', $abn))
                ->when(! $abn && $acn, fn ($q) => $q->where('acn', $acn))
                ->when(! $abn && ! $acn, fn ($q) => $q->where('legal_name', $legalName))
                ->first();

            if ($entity && (int) $entity->user_id !== $userId) {
                $skipped++;
                $warnings[] = "Row {$row}: skipped — {$legalName} matches entity id {$entity->id} owned by user {$entity->user_id}.";

                return;
            }

            $noteParts = array_filter([
                $trustOf !== '' ? "Under trust of: {$trustOf}" : null,
                $statusLabel !== '' ? "Classification: {$statusLabel}" : null,
            ]);
            $noteBody = $noteParts !== [] ? implode("\n", $noteParts) : null;

            $payload = [
                'legal_name' => $legalName,
                'entity_type' => 'Company',
                'abn' => $abn,
                'acn' => $acn,
                'registered_address' => $address !== '' ? $address : '—',
                'asic_renewal_date' => $asicRenewal,
                'user_id' => $userId,
                'status' => 'Active',
            ];

            if ($dryRun) {
                $entity ? $updated++ : $created++;

                return;
            }

            DB::transaction(function () use (
                $entity,
                $payload,
                $noteBody,
                $userId,
                $skipDirectors,
                $directorName,
                &$created,
                &$updated,
                &$directorsLinked
            ): void {
                if ($entity) {
                    $entity->update($payload);
                    $updated++;
                    $be = $entity;
                } else {
                    $be = BusinessEntity::query()->create($payload);
                    $created++;
                }

                if ($noteBody !== null) {
                    $hasNote = Note::query()
                        ->where('business_entity_id', $be->id)
                        ->where('user_id', $userId)
                        ->where('content', $noteBody)
                        ->exists();
                    if (! $hasNote) {
                        Note::query()->create([
                            'business_entity_id' => $be->id,
                            'user_id' => $userId,
                            'content' => $noteBody,
                            'is_reminder' => false,
                        ]);
                    }
                }

                if ($skipDirectors || $directorName === '') {
                    return;
                }

                [$first, $last] = $this->splitPersonName($directorName);
                if ($first === null || $last === null) {
                    return;
                }

                $person = Person::query()->lazy()->first(
                    fn (Person $p) => (string) $p->first_name === $first && (string) $p->last_name === $last
                );

                if (! $person) {
                    $person = Person::query()->create([
                        'first_name' => $first,
                        'last_name' => $last,
                        'status' => 'Active',
                    ]);
                }

                $already = EntityPerson::query()
                    ->where('business_entity_id', $be->id)
                    ->where('person_id', $person->id)
                    ->where('role', 'Director')
                    ->where('role_status', 'Active')
                    ->exists();

                if (! $already) {
                    EntityPerson::query()->create([
                        'business_entity_id' => $be->id,
                        'person_id' => $person->id,
                        'role' => 'Director',
                        'appointment_date' => Carbon::now()->startOfDay(),
                        'role_status' => 'Active',
                    ]);
                    $directorsLinked++;
                }
            });
        };

        for ($row = 3; $row <= $highestRow; $row++) {
            $processRow($row);
        }

        foreach ($warnings as $w) {
            $this->warn($w);
        }

        $this->info($dryRun ? '[dry-run] rows that would be created / updated:' : 'Import finished.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped (other owner)', $skipped],
                ['Director links added', $directorsLinked],
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

    private function normalizeAbn(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $digits = preg_replace('/\D/', '', (string) (int) $value);

            return $digits !== '' ? $digits : null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    private function normalizeAcn(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $digits = preg_replace('/\D/', '', (string) (int) $value);

            return $digits !== '' ? $digits : null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? $digits : null;
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

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitPersonName(string $full): array
    {
        $full = trim($full);
        if ($full === '') {
            return [null, null];
        }
        $parts = preg_split('/\s+/u', $full, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return [null, null];
        }
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }
        $last = array_pop($parts);
        $first = implode(' ', $parts);

        return [$first, $last];
    }
}
