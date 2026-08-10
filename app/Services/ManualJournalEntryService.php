<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualJournalEntryService
{
    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string}>  $lines
     */
    public function post(
        BusinessEntity $businessEntity,
        string $entryDate,
        string $description,
        array $lines,
        ?string $referenceNumber = null
    ): JournalEntry {
        return DB::transaction(function () use ($businessEntity, $entryDate, $description, $lines, $referenceNumber) {
            $normalized = $this->normalizeLines($lines);
            $this->assertBalanced($normalized);

            $entry = new JournalEntry;
            $entry->business_entity_id = $businessEntity->id;
            $entry->entry_date = $entryDate;
            $entry->reference_number = $referenceNumber ?: $this->nextManualReference();
            $entry->description = $description;
            $entry->is_posted = true;
            $entry->created_by = $businessEntity->user_id ?? auth()->id();
            $entry->source_type = null;
            $entry->source_id = null;

            $totalDebit = 0.0;
            $totalCredit = 0.0;
            foreach ($normalized as $line) {
                $totalDebit += $line['debit'];
                $totalCredit += $line['credit'];
            }

            $entry->total_debit = $totalDebit;
            $entry->total_credit = $totalCredit;
            $entry->save();

            foreach ($normalized as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'debit_amount' => $line['debit'],
                    'credit_amount' => $line['credit'],
                    'description' => $line['description'] ?? null,
                    'reference' => 'MAN:'.$entry->id,
                ]);
            }

            return $entry->load('journalLines');
        });
    }

    /**
     * Post a single-account opening balance for one entity (debit − credit = $netBalance).
     */
    public function postOpeningBalance(
        BusinessEntity $businessEntity,
        ChartOfAccount $account,
        float $netBalance,
        string $asOfDate
    ): ?JournalEntry {
        if (abs($netBalance) < 0.00001) {
            return null;
        }

        $equity = $this->ensureOpeningBalanceEquityAccount();
        $abs = round(abs($netBalance), 2);

        if ($netBalance > 0) {
            $lines = [
                ['chart_of_account_id' => $account->id, 'debit' => $abs, 'credit' => 0.0, 'description' => 'Opening balance'],
                ['chart_of_account_id' => $equity->id, 'debit' => 0.0, 'credit' => $abs, 'description' => 'Opening balance offset'],
            ];
        } else {
            $lines = [
                ['chart_of_account_id' => $account->id, 'debit' => 0.0, 'credit' => $abs, 'description' => 'Opening balance'],
                ['chart_of_account_id' => $equity->id, 'debit' => $abs, 'credit' => 0.0, 'description' => 'Opening balance offset'],
            ];
        }

        return $this->post(
            $businessEntity,
            $asOfDate,
            'Opening balance for '.$account->account_code.' '.$account->account_name,
            $lines,
            'OPEN-'.$account->account_code.'-'.$businessEntity->id
        );
    }

    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string}>  $lines
     * @return list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string}>
     */
    private function normalizeLines(array $lines): array
    {
        $normalized = [];
        foreach ($lines as $line) {
            $accountId = (int) ($line['chart_of_account_id'] ?? 0);
            if ($accountId <= 0) {
                continue;
            }

            $debit = round(max(0, (float) ($line['debit'] ?? 0)), 2);
            $credit = round(max(0, (float) ($line['credit'] ?? 0)), 2);
            if ($debit === 0.0 && $credit === 0.0) {
                continue;
            }

            $normalized[] = [
                'chart_of_account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
            ];
        }

        if ($normalized === []) {
            throw new \InvalidArgumentException('At least one journal line with a debit or credit is required.');
        }

        return $normalized;
    }

    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string}>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $line) {
            $totalDebit += $line['debit'];
            $totalCredit += $line['credit'];
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \DomainException(
                'Journal entry is not balanced: debits '.number_format($totalDebit, 2)
                .' vs credits '.number_format($totalCredit, 2)
            );
        }
    }

    private function nextManualReference(): string
    {
        return 'MAN-'.Str::upper(Str::random(8));
    }

    private function ensureOpeningBalanceEquityAccount(): ChartOfAccount
    {
        $code = (string) config('financial.report_accounts.opening_balance_equity', '3190');

        return ChartOfAccount::query()->where('account_code', $code)->first()
            ?? ChartOfAccount::create([
                'account_code' => $code,
                'account_name' => 'Opening Balance Equity',
                'account_type' => 'equity',
                'account_category' => 'equity',
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
            ]);
    }
}
