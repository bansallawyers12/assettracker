<?php

namespace App\Console\Commands;

use App\Models\ComplianceDocumentFile;
use App\Services\AtoDueDateService;
use Illuminate\Console\Command;

/**
 * Backfill estimated ATO/ASIC due dates onto existing entity compliance slots
 * that still have a null due_date. Does not overwrite manually set dates unless --force.
 */
class BackfillComplianceDueDates extends Command
{
    protected $signature = 'compliance:backfill-due-dates
                            {--dry-run : Show what would change without writing}
                            {--force : Overwrite existing due dates with calculated values}
                            {--chunk=200 : Rows per chunk}';

    protected $description = 'Backfill estimated ATO/ASIC due dates on entity compliance document files';

    public function handle(AtoDueDateService $dueDates): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = ComplianceDocumentFile::query()
            ->whereNotNull('compliance_document_type_id')
            ->whereHas('yearRecord', fn ($q) => $q->whereNull('asset_id'))
            ->with(['type', 'yearRecord.businessEntity']);

        if (! $force) {
            $query->whereNull('due_date');
        }

        $updated = 0;
        $skipped = 0;
        $unchanged = 0;

        $query->orderBy('id')->chunkById($chunk, function ($files) use ($dueDates, $dryRun, $force, &$updated, &$skipped, &$unchanged) {
            foreach ($files as $file) {
                $type = $file->type;
                $record = $file->yearRecord;

                if ($type === null || $record === null || $record->asset_id !== null) {
                    $skipped++;

                    continue;
                }

                $calculated = $dueDates->dueDateForType($type, $record);
                if ($calculated === null) {
                    $skipped++;

                    continue;
                }

                $newDate = $calculated->toDateString();
                $current = $file->due_date?->toDateString();

                if ($current === $newDate) {
                    $unchanged++;

                    continue;
                }

                if (! $force && $current !== null) {
                    $skipped++;

                    continue;
                }

                $this->line(sprintf(
                    '%s #%d %s FY %s: %s → %s',
                    $dryRun ? '[dry-run]' : '[update]',
                    $file->id,
                    $type->code,
                    $record->fy_start_date?->toDateString() ?? '?',
                    $current ?? 'null',
                    $newDate
                ));

                if (! $dryRun) {
                    $file->update(['due_date' => $newDate]);
                }

                $updated++;
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Done. %s %d, unchanged %d, skipped %d.%s',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            $unchanged,
            $skipped,
            $force ? ' (--force)' : ''
        ));

        return self::SUCCESS;
    }
}
