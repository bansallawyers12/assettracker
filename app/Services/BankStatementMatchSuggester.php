<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BankStatementMatchSuggester
{
    public const AMOUNT_TOLERANCE = 0.01;

    public const HIGH_DATE_DAYS = 3;

    public const MEDIUM_DATE_DAYS = 14;

    /**
     * @param  Collection<int, Transaction>  $candidates
     * @return array{
     *     action: string,
     *     confidence: string,
     *     reason: string|null,
     *     transaction_id: int|null,
     *     transaction_type: string|null,
     *     chart_account_id: int|null,
     *     asset_id: int|null,
     *     invoice_id: int|null,
     *     alternates: list<array{transaction_id: int, confidence: string, reason: string}>
     * }
     */
    public function suggest(
        BankStatementEntry $entry,
        BankAccount $bankAccount,
        Collection $candidates,
        ?int $defaultAssetId = null
    ): array {
        $none = $this->none();

        $match = $this->suggestMatch($entry, $candidates);
        if ($match !== null) {
            return $match;
        }

        if ($bankAccount->account_purpose === BankAccount::PURPOSE_LOAN) {
            $loan = $this->suggestLoanCreate($entry, $defaultAssetId);
            if ($loan !== null) {
                return $loan;
            }
        }

        if ($bankAccount->account_purpose === BankAccount::PURPOSE_OFFSET) {
            $offset = $this->suggestOffsetCreate($entry, $defaultAssetId);
            if ($offset !== null) {
                return $offset;
            }
        }

        $keywordType = $this->determineTransactionType(
            (string) ($entry->description ?? ''),
            (float) $entry->amount
        );

        if ($keywordType !== null && $keywordType !== 'unknown') {
            if ($bankAccount->account_purpose === BankAccount::PURPOSE_OFFSET
                && in_array($keywordType, LoanOffsetTransactionGuard::LOAN_ECONOMIC_TYPES, true)) {
                return $this->createSuggestion(
                    Transaction::TYPE_INTERNAL_TRANSFER,
                    'medium',
                    'Offset account: use internal transfer instead of '.$keywordType,
                    $defaultAssetId
                );
            }

            return [
                'action' => 'create_transaction',
                'confidence' => 'medium',
                'reason' => 'Keyword rule: '.$keywordType,
                'transaction_id' => null,
                'transaction_type' => $keywordType,
                'chart_account_id' => null,
                'asset_id' => $defaultAssetId,
                'invoice_id' => null,
                'alternates' => [],
            ];
        }

        return $none;
    }

    /**
     * Suggest for many entries, claiming each matched transaction at most once.
     *
     * @param  Collection<int, BankStatementEntry>  $entries
     * @param  Collection<int, Transaction>  $candidates
     * @return array<int, array<string, mixed>>
     */
    public function suggestMany(
        Collection $entries,
        BankAccount $bankAccount,
        Collection $candidates,
        ?int $defaultAssetId = null
    ): array {
        $out = [];
        $available = $candidates->values();

        foreach ($entries as $entry) {
            $suggestion = $this->suggest($entry, $bankAccount, $available, $defaultAssetId);
            $out[(int) $entry->id] = $suggestion;

            if (($suggestion['action'] ?? null) === 'match_transaction'
                && ! empty($suggestion['transaction_id'])) {
                $claimedId = (int) $suggestion['transaction_id'];
                $available = $available
                    ->reject(fn (Transaction $transaction) => (int) $transaction->id === $claimedId)
                    ->values();
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, Transaction>  $candidates
     * @return array<string, mixed>|null
     */
    private function suggestMatch(BankStatementEntry $entry, Collection $candidates): ?array
    {
        $entryAmount = abs((float) $entry->amount);
        $entryIsIncome = (float) $entry->amount >= 0;
        $entryDate = $this->asDate($entry->date);

        $ranked = [];

        foreach ($candidates as $transaction) {
            $txAmount = abs((float) $transaction->amount);
            if (abs($entryAmount - $txAmount) > self::AMOUNT_TOLERANCE) {
                continue;
            }

            $txIsIncome = Transaction::directionFromType(
                (string) $transaction->transaction_type,
                (float) $entry->amount
            ) === 'income';
            if (! Transaction::isInternalTransfer((string) $transaction->transaction_type)
                && $entryIsIncome !== $txIsIncome) {
                continue;
            }

            $days = null;
            $txDate = $this->asDate($transaction->date);
            if ($entryDate && $txDate) {
                $days = (int) abs(
                    $entryDate->copy()->startOfDay()
                        ->diffInDays($txDate->copy()->startOfDay())
                );
            }

            if ($days !== null && $days > self::MEDIUM_DATE_DAYS) {
                continue;
            }

            $confidence = 'medium';
            $reason = 'Amount and direction match';
            if ($days !== null && $days <= self::HIGH_DATE_DAYS) {
                $confidence = 'high';
                $reason = 'Exact amount, direction, date within '.self::HIGH_DATE_DAYS.' days';
            } elseif ($days !== null) {
                $reason = 'Amount match, date within '.self::MEDIUM_DATE_DAYS.' days';
            }

            $ranked[] = [
                'transaction' => $transaction,
                'confidence' => $confidence,
                'reason' => $reason,
                'days' => $days ?? 999,
                'unpaid' => ($transaction->payment_status ?? 'paid') === 'unpaid' ? 0 : 1,
            ];
        }

        if ($ranked === []) {
            return null;
        }

        usort($ranked, function (array $a, array $b): int {
            $confRank = ['high' => 0, 'medium' => 1, 'low' => 2];
            $byConf = ($confRank[$a['confidence']] ?? 9) <=> ($confRank[$b['confidence']] ?? 9);
            if ($byConf !== 0) {
                return $byConf;
            }

            $byUnpaid = $a['unpaid'] <=> $b['unpaid'];
            if ($byUnpaid !== 0) {
                return $byUnpaid;
            }

            return $a['days'] <=> $b['days'];
        });

        $best = $ranked[0];
        $alternates = [];
        foreach (array_slice($ranked, 1, 5) as $alt) {
            $alternates[] = [
                'transaction_id' => (int) $alt['transaction']->id,
                'confidence' => $alt['confidence'],
                'reason' => $alt['reason'],
            ];
        }

        $confidence = $best['confidence'];
        if (count($ranked) > 1 && $confidence === 'high') {
            $confidence = 'medium';
            $best['reason'] = 'Multiple amount matches; best by date';
        }

        return [
            'action' => 'match_transaction',
            'confidence' => $confidence,
            'reason' => $best['reason'],
            'transaction_id' => (int) $best['transaction']->id,
            'transaction_type' => $best['transaction']->transaction_type,
            'chart_account_id' => null,
            'asset_id' => $best['transaction']->asset_id ? (int) $best['transaction']->asset_id : null,
            'invoice_id' => null,
            'alternates' => $alternates,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function suggestLoanCreate(BankStatementEntry $entry, ?int $defaultAssetId): ?array
    {
        $meta = is_array($entry->meta) ? $entry->meta : [];
        $subcategory = strtolower(trim((string) ($meta['subcategory'] ?? '')));
        $description = strtolower((string) ($entry->description ?? ''));
        $combined = trim($subcategory.' '.$description);

        if ($subcategory !== '' || ($meta['bank_profile'] ?? null) === 'macquarie') {
            if (str_contains($combined, 'dishonour')) {
                return [
                    'action' => 'none',
                    'confidence' => 'low',
                    'reason' => 'Dishonour — review manually',
                    'transaction_id' => null,
                    'transaction_type' => null,
                    'chart_account_id' => null,
                    'asset_id' => $defaultAssetId,
                    'invoice_id' => null,
                    'alternates' => [],
                ];
            }

            if (str_contains($subcategory, 'interest') || preg_match('/\binterest\b/', $description)) {
                return $this->createSuggestion('loan_interest', 'high', 'Subcategory: Interest', $defaultAssetId);
            }

            if (
                str_contains($subcategory, 'fee')
                || str_contains($subcategory, 'package')
                || preg_match('/\b(fee|package fee|other fees)\b/', $combined)
            ) {
                return $this->createSuggestion('loan_fees', 'high', 'Subcategory: Fees', $defaultAssetId);
            }

            if (
                str_contains($subcategory, 'transfer')
                || str_contains($subcategory, 'repayment')
                || preg_match('/\b(transfer|from account|loan repayment|redraw)\b/', $combined)
            ) {
                return $this->createSuggestion('loan_repayments', 'high', 'Subcategory: Transfer/repayment', $defaultAssetId);
            }
        }

        if (preg_match('/\binterest\b/', $description) && (float) $entry->amount < 0) {
            return $this->createSuggestion('loan_interest', 'medium', 'Keyword: interest', $defaultAssetId);
        }

        if (preg_match('/\b(fee|package fee)\b/', $description) && (float) $entry->amount < 0) {
            return $this->createSuggestion('loan_fees', 'medium', 'Keyword: fee', $defaultAssetId);
        }

        if (preg_match('/\b(loan repayment|mortgage|principal|redraw)\b/', $description) && (float) $entry->amount < 0) {
            return $this->createSuggestion('loan_repayments', 'medium', 'Keyword: repayment', $defaultAssetId);
        }

        return null;
    }

    /**
     * Offset cash movements that look like loan funding should not book loan economics.
     *
     * @return array<string, mixed>|null
     */
    private function suggestOffsetCreate(BankStatementEntry $entry, ?int $defaultAssetId): ?array
    {
        $meta = is_array($entry->meta) ? $entry->meta : [];
        $subcategory = strtolower(trim((string) ($meta['subcategory'] ?? '')));
        $description = strtolower((string) ($entry->description ?? ''));
        $combined = trim($subcategory.' '.$description);

        if (
            str_contains($subcategory, 'interest')
            || str_contains($subcategory, 'fee')
            || preg_match('/\b(interest|package fee|other fees)\b/', $combined)
        ) {
            return [
                'action' => 'none',
                'confidence' => 'low',
                'reason' => 'Interest/fees belong on the loan account — review manually',
                'transaction_id' => null,
                'transaction_type' => null,
                'chart_account_id' => null,
                'asset_id' => $defaultAssetId,
                'invoice_id' => null,
                'alternates' => [],
            ];
        }

        if (
            str_contains($subcategory, 'transfer')
            || str_contains($subcategory, 'repayment')
            || preg_match('/\b(transfer|loan repayment|redraw|to loan|from loan|mortgage)\b/', $combined)
        ) {
            return $this->createSuggestion(
                Transaction::TYPE_INTERNAL_TRANSFER,
                'high',
                'Offset transfer — not a loan repayment',
                $defaultAssetId
            );
        }

        if (preg_match('/\b(loan repayment|mortgage|redraw|transfer to loan|from offset)\b/', $description)) {
            return $this->createSuggestion(
                Transaction::TYPE_INTERNAL_TRANSFER,
                'medium',
                'Offset keyword: internal transfer',
                $defaultAssetId
            );
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function createSuggestion(string $type, string $confidence, string $reason, ?int $assetId): array
    {
        return [
            'action' => 'create_transaction',
            'confidence' => $confidence,
            'reason' => $reason,
            'transaction_id' => null,
            'transaction_type' => $type,
            'chart_account_id' => null,
            'asset_id' => $assetId,
            'invoice_id' => null,
            'alternates' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function none(): array
    {
        return [
            'action' => 'none',
            'confidence' => 'low',
            'reason' => null,
            'transaction_id' => null,
            'transaction_type' => null,
            'chart_account_id' => null,
            'asset_id' => null,
            'invoice_id' => null,
            'alternates' => [],
        ];
    }

    private function asDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Keyword rules migrated from BusinessEntityController::determineTransactionType.
     */
    public function determineTransactionType(string $description, float $amount): string
    {
        $description = strtolower($description);

        if ($amount > 0) {
            if (preg_match('/sale|invoice|revenue|payment received/i', $description)) {
                return 'sales_revenue';
            }
            if (preg_match('/interest/i', $description)) {
                return 'interest_income';
            }
            if (preg_match('/rent received|rental income/i', $description)) {
                return 'rental_income';
            }
            if (preg_match('/grant|subsidy/i', $description)) {
                return 'grants_subsidies';
            }
            if (preg_match('/director loan|loan from director/i', $description)) {
                return 'director_loan_in';
            }
            if (preg_match('/related party sale/i', $description)) {
                return 'sales_to_related_party';
            }
        } elseif ($amount < 0) {
            if (preg_match('/cogs|cost of goods|inventory purchase/i', $description)) {
                return 'cogs';
            }
            if (preg_match('/wages|salary|payroll|superannuation|super fund/i', $description)) {
                return 'wages_superannuation';
            }
            if (preg_match('/rent payment|lease|electricity|water|gas|internet|phone bill|utilities/i', $description)) {
                return 'rent_utilities';
            }
            if (preg_match('/marketing|advertising|google ads|facebook ads|seo/i', $description)) {
                return 'marketing_advertising';
            }
            if (preg_match('/travel|flight|hotel|accommodation|uber|taxi/i', $description)) {
                return 'travel_expenses';
            }
            if (preg_match('/loan repayment|mortgage payment/i', $description)) {
                return 'loan_repayments';
            }
            if (preg_match('/capital purchase|asset purchase|vehicle|equipment|computer/i', $description)) {
                return 'capital_expenditure';
            }
            if (preg_match('/bas payment|gst payment|payg payment|tax office|ato/i', $description)) {
                return 'bas_payments';
            }
            if (preg_match('/director loan repayment|repay director/i', $description)) {
                return 'repayment_directors_loans';
            }
            if (preg_match('/loan to director|advance to director/i', $description)) {
                return 'company_loans_to_directors';
            }
            if (preg_match('/director fee|directors fee/i', $description)) {
                return 'directors_fees';
            }
            if (preg_match('/related party rent/i', $description)) {
                return 'rent_to_related_party';
            }
            if (preg_match('/related party purchase/i', $description)) {
                return 'purchases_from_related_party';
            }
        }

        return 'unknown';
    }
}
