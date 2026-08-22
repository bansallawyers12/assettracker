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
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>  $lines
     */
    public function post(
        BusinessEntity $businessEntity,
        string $entryDate,
        string $description,
        array $lines,
        ?string $referenceNumber = null,
        ?int $reversesJournalEntryId = null
    ): JournalEntry {
        return DB::transaction(function () use (
            $businessEntity,
            $entryDate,
            $description,
            $lines,
            $referenceNumber,
            $reversesJournalEntryId
        ) {
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
            $entry->reverses_journal_entry_id = $reversesJournalEntryId;
            $this->fillTotals($entry, $normalized);
            $entry->save();

            $this->replaceLines($entry, $normalized);

            return $entry->load('journalLines');
        });
    }

    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>  $lines
     */
    public function update(
        JournalEntry $entry,
        string $entryDate,
        string $description,
        array $lines,
        ?string $referenceNumber = null
    ): JournalEntry {
        return DB::transaction(function () use ($entry, $entryDate, $description, $lines, $referenceNumber) {
            $entry->refresh();
            $this->assertEditable($entry);

            $normalized = $this->normalizeLines($lines);
            $this->assertBalanced($normalized);

            $entry->entry_date = $entryDate;
            $entry->description = $description;
            if (! $entry->isOpeningBalance() && $referenceNumber) {
                $entry->reference_number = $referenceNumber;
            }
            $this->fillTotals($entry, $normalized);
            $entry->save();

            $this->replaceLines($entry, $normalized);

            return $entry->load('journalLines');
        });
    }

    public function reverse(JournalEntry $entry, string $entryDate): JournalEntry
    {
        return DB::transaction(function () use ($entry, $entryDate) {
            $entry->refresh();
            $this->assertReversible($entry);

            return $this->postOffset($entry, $entryDate, 'Reversal of '.$entry->reference_number, 'REV-');
        });
    }

    public function void(JournalEntry $entry): JournalEntry
    {
        return DB::transaction(function () use ($entry) {
            $entry->refresh();
            $this->assertVoidable($entry);

            $offset = $this->postOffset(
                $entry,
                $entry->entry_date->toDateString(),
                'Void of '.$entry->reference_number,
                'VOID-'
            );

            $entry->voided_at = now();
            $entry->save();

            return $offset;
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

        $reference = 'OPEN-'.$account->account_code.'-'.$businessEntity->id;

        if (JournalEntry::query()->where('reference_number', $reference)->exists()) {
            throw new \DomainException(
                "Opening balance journal {$reference} already exists for this entity and account."
            );
        }

        return $this->post(
            $businessEntity,
            $asOfDate,
            'Opening balance for '.$account->account_code.' '.$account->account_name,
            $lines,
            $reference
        );
    }

    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>  $normalized
     */
    private function fillTotals(JournalEntry $entry, array $normalized): void
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($normalized as $line) {
            $totalDebit += $line['debit'];
            $totalCredit += $line['credit'];
        }

        $entry->total_debit = $totalDebit;
        $entry->total_credit = $totalCredit;
    }

    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>  $normalized
     */
    private function replaceLines(JournalEntry $entry, array $normalized): void
    {
        $entry->journalLines()->delete();

        foreach ($normalized as $line) {
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $line['chart_of_account_id'],
                'debit_amount' => $line['debit'],
                'credit_amount' => $line['credit'],
                'description' => $line['description'] ?? null,
                'reference' => 'MAN:'.$entry->id,
                'tracking_category_id' => $line['tracking_category_id'] ?? null,
                'tracking_sub_category_id' => $line['tracking_sub_category_id'] ?? null,
            ]);
        }
    }

    private function postOffset(JournalEntry $entry, string $entryDate, string $description, string $prefix): JournalEntry
    {
        $entry->loadMissing(['journalLines', 'businessEntity']);

        $lines = [];
        foreach ($entry->journalLines as $line) {
            $lines[] = [
                'chart_of_account_id' => (int) $line->chart_of_account_id,
                'debit' => (float) $line->credit_amount,
                'credit' => (float) $line->debit_amount,
                'description' => $line->description,
                'tracking_category_id' => $line->tracking_category_id,
                'tracking_sub_category_id' => $line->tracking_sub_category_id,
            ];
        }

        $businessEntity = $entry->businessEntity;
        if (! $businessEntity) {
            throw new \DomainException('Journal is missing its entity.');
        }

        return $this->post(
            $businessEntity,
            $entryDate,
            $description,
            $lines,
            $this->nextOffsetReference($prefix, (string) $entry->reference_number),
            (int) $entry->id
        );
    }

    private function assertUserPostedManual(JournalEntry $entry): void
    {
        if ($entry->source_type !== null || ! $entry->is_posted) {
            throw new \DomainException('Only posted manual journals can be changed.');
        }
    }

    private function assertEditable(JournalEntry $entry): void
    {
        $this->assertUserPostedManual($entry);

        if ($entry->isReversal()) {
            throw new \DomainException('Reversal journals cannot be edited.');
        }

        if ($entry->isVoided()) {
            throw new \DomainException('Voided journals cannot be edited.');
        }

        if ($entry->reversedBy()->exists()) {
            throw new \DomainException('This journal has been reversed or voided. Post a new journal instead of editing it.');
        }
    }

    private function assertReversible(JournalEntry $entry): void
    {
        $this->assertUserPostedManual($entry);

        if ($entry->isVoided()) {
            throw new \DomainException('This journal is already voided.');
        }

        if ($entry->reversedBy()->exists()) {
            throw new \DomainException('This journal already has a reversal.');
        }
    }

    private function assertVoidable(JournalEntry $entry): void
    {
        $this->assertReversible($entry);

        if ($entry->isReversal()) {
            throw new \DomainException('Void the original journal instead of voiding a reversal.');
        }
    }

    private function nextOffsetReference(string $prefix, string $originalReference): string
    {
        $base = Str::limit($prefix.$originalReference, 46, '');
        $candidate = $base;
        $suffix = 2;

        while (JournalEntry::query()->where('reference_number', $candidate)->exists()) {
            $candidate = Str::limit($base.'-'.$suffix, 50, '');
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param  list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>  $lines
     * @return list<array{chart_of_account_id: int, debit: float, credit: float, description?: ?string, tracking_category_id?: ?int, tracking_sub_category_id?: ?int}>
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

            $trackingCategoryId = isset($line['tracking_category_id']) && $line['tracking_category_id'] !== ''
                ? (int) $line['tracking_category_id']
                : null;
            $trackingSubCategoryId = isset($line['tracking_sub_category_id']) && $line['tracking_sub_category_id'] !== ''
                ? (int) $line['tracking_sub_category_id']
                : null;

            $normalized[] = [
                'chart_of_account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
                'tracking_category_id' => $trackingCategoryId > 0 ? $trackingCategoryId : null,
                'tracking_sub_category_id' => $trackingSubCategoryId > 0 ? $trackingSubCategoryId : null,
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
