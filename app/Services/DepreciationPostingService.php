<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepreciationPostingService
{
    public function __construct(private ManualJournalEntryService $manualJournalService) {}

    /**
     * @return array{posted: int, skipped: int}
     */
    public function postMonthlyForDate(Carbon $asOfDate): array
    {
        $posted = 0;
        $skipped = 0;

        $expenseAccount = $this->findAccount((string) config('financial.report_accounts.depreciation_expense', '5195'));
        $accumulatedAccount = $this->findAccount((string) config('financial.report_accounts.accumulated_depreciation', '1590'));

        if (! $expenseAccount || ! $accumulatedAccount) {
            throw new \RuntimeException('Depreciation expense or accumulated depreciation accounts are missing. Run ChartOfAccountSeeder.');
        }

        $assets = Asset::query()
            ->where('is_depreciable', true)
            ->whereNotNull('acquisition_cost')
            ->whereNotNull('acquisition_date')
            ->get();

        foreach ($assets as $asset) {
            if (! $asset->business_entity_id) {
                $skipped++;

                continue;
            }

            $businessEntity = BusinessEntity::query()->find($asset->business_entity_id);
            if (! $businessEntity) {
                $skipped++;

                continue;
            }

            $monthKey = $asOfDate->format('Y-m');
            $reference = 'DEP-'.$asset->id.'-'.$monthKey;

            if (JournalEntry::query()->where('reference_number', $reference)->exists()) {
                $skipped++;

                continue;
            }

            $calculated = round((float) $asset->calculateDepreciation($asOfDate), 2);
            $alreadyPosted = round((float) ($asset->accumulated_depreciation ?? 0), 2);
            $increment = round($calculated - $alreadyPosted, 2);

            if ($increment <= 0) {
                $skipped++;

                continue;
            }

            DB::transaction(function () use (
                $asset,
                $businessEntity,
                $asOfDate,
                $reference,
                $increment,
                $calculated,
                $expenseAccount,
                $accumulatedAccount
            ) {
                $entry = $this->manualJournalService->post(
                    $businessEntity,
                    $asOfDate->toDateString(),
                    'Depreciation — '.$asset->name,
                    [
                        [
                            'chart_of_account_id' => $expenseAccount->id,
                            'debit' => $increment,
                            'credit' => 0,
                            'description' => 'Depreciation expense',
                        ],
                        [
                            'chart_of_account_id' => $accumulatedAccount->id,
                            'debit' => 0,
                            'credit' => $increment,
                            'description' => 'Accumulated depreciation',
                        ],
                    ],
                    $reference
                );

                $entry->source_type = Asset::class;
                $entry->source_id = $asset->id;
                $entry->save();

                $asset->accumulated_depreciation = $calculated;
                $asset->book_value = max(0, round((float) $asset->acquisition_cost - $calculated, 2));
                $asset->save();
            });

            $posted++;
        }

        return ['posted' => $posted, 'skipped' => $skipped];
    }

    private function findAccount(string $code): ?ChartOfAccount
    {
        return ChartOfAccount::query()
            ->where('account_code', $code)
            ->where('is_active', true)
            ->first()
            ?? ChartOfAccount::query()->where('account_code', $code)->first();
    }
}
