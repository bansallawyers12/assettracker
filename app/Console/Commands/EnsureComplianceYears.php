<?php

namespace App\Console\Commands;

use App\Models\BusinessEntity;
use App\Services\ComplianceYearService;
use App\Support\FinancialYear;
use Illuminate\Console\Command;

/**
 * Ensure entity compliance year records and slots exist for a FY range
 * without requiring the workspace UI to be opened.
 */
class EnsureComplianceYears extends Command
{
    protected $signature = 'compliance:ensure-years
                            {--fy-from= : First FY start date (Y-m-d); defaults to oldest in years_shown}
                            {--fy-to= : Last FY start date (Y-m-d); defaults to current FY}
                            {--entity= : Limit to a business entity ID}
                            {--dry-run : Show what would be created without writing}';

    protected $description = 'Provision compliance year records and ATO/ASIC slots for a FY range';

    public function handle(ComplianceYearService $yearService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $years = $yearService->listAvailableYears();
        $oldest = $years !== [] ? $years[array_key_last($years)]['start'] : FinancialYear::currentStart()->toDateString();
        $newest = $years !== [] ? $years[0]['start'] : FinancialYear::currentStart()->toDateString();

        try {
            $fyFrom = $yearService->normalizeFyStart($this->option('fy-from') ?: $oldest);
            $fyTo = $yearService->normalizeFyStart($this->option('fy-to') ?: $newest);
        } catch (\Throwable $e) {
            $this->error('Invalid FY date: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($fyFrom->gt($fyTo)) {
            [$fyFrom, $fyTo] = [$fyTo, $fyFrom];
        }

        $entitiesQuery = BusinessEntity::query()->forFinancialReports()->orderBy('legal_name');
        if ($this->option('entity')) {
            $entitiesQuery->where('id', (int) $this->option('entity'));
        }
        $entities = $entitiesQuery->get();

        if ($entities->isEmpty()) {
            $this->warn('No reporting entities found.');

            return self::SUCCESS;
        }

        $fyStarts = [];
        $cursor = $fyFrom->copy();
        while ($cursor->lte($fyTo)) {
            $fyStarts[] = $cursor->copy();
            $cursor->addYear();
        }

        $created = 0;
        $provisioned = 0;

        foreach ($entities as $entity) {
            foreach ($fyStarts as $fyStart) {
                $exists = $entity->complianceYearRecords()
                    ->whereNull('asset_id')
                    ->whereDate('fy_start_date', $fyStart->toDateString())
                    ->exists();

                $this->line(sprintf(
                    '%s %s FY %s',
                    $dryRun ? '[dry-run]' : ($exists ? '[provision]' : '[create]'),
                    $entity->legal_name,
                    FinancialYear::label($fyStart)
                ));

                if ($dryRun) {
                    continue;
                }

                if (! $exists) {
                    $created++;
                }
                $yearService->findOrCreateYearRecord($entity, null, $fyStart);
                $provisioned++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Entities %d, FY years %d, records created %d, provisioned %d.%s',
            $entities->count(),
            count($fyStarts),
            $dryRun ? 0 : $created,
            $dryRun ? 0 : $provisioned,
            $dryRun ? ' (dry-run)' : ''
        ));

        return self::SUCCESS;
    }
}
