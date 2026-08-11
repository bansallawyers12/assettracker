<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use Illuminate\Validation\ValidationException;

/**
 * Keeps loan economics on the loan account and treats offset↔loan as transfers.
 */
class LoanOffsetTransactionGuard
{
    /** @var list<string> */
    public const LOAN_ECONOMIC_TYPES = [
        'loan_repayments',
        'loan_interest',
        'loan_fees',
    ];

    public function assertAllowed(
        BankAccount $bankAccount,
        string $transactionType,
        ?BusinessEntity $entity = null,
        ?int $counterpartBankAccountId = null,
        bool $requireCounterpart = true,
        ?int $assetId = null
    ): void {
        if ($transactionType === Transaction::TYPE_INTERNAL_TRANSFER) {
            $resolvedCounterpart = $counterpartBankAccountId
                ?? $this->suggestCounterpartBankAccountId($bankAccount, $entity, $assetId);

            $this->assertInternalTransfer(
                $bankAccount,
                $entity,
                $resolvedCounterpart,
                $requireCounterpart
            );

            return;
        }

        if (! in_array($transactionType, self::LOAN_ECONOMIC_TYPES, true)) {
            return;
        }

        if (! $this->isOffsetAccount($bankAccount, $entity)) {
            return;
        }

        throw ValidationException::withMessages([
            'transaction_type' => 'Loan interest, fees, and repayments must be recorded on the loan account, not the offset account. Use Internal transfer for money moved between offset and loan.',
        ]);
    }

    /**
     * Prefer an explicit counterpart; otherwise the asset's linked loan when booking on offset.
     */
    public function suggestCounterpartBankAccountId(
        BankAccount $bankAccount,
        ?BusinessEntity $entity = null,
        ?int $assetId = null
    ): ?int {
        if ($assetId === null || ! $this->isOffsetAccount($bankAccount, $entity)) {
            return null;
        }

        $asset = Asset::query()->find($assetId);
        $loan = $asset?->linkedLoanAccount();

        if ($loan === null || (int) $loan->id === (int) $bankAccount->id) {
            return null;
        }

        return (int) $loan->id;
    }

    public function isOffsetAccount(BankAccount $bankAccount, ?BusinessEntity $entity = null): bool
    {
        if ($bankAccount->account_purpose === BankAccount::PURPOSE_OFFSET) {
            return true;
        }

        if ($entity === null) {
            return false;
        }

        return $bankAccount->hasLinkOnEntity($entity, BankAccount::PURPOSE_OFFSET);
    }

    private function assertInternalTransfer(
        BankAccount $bankAccount,
        ?BusinessEntity $entity,
        ?int $counterpartBankAccountId,
        bool $requireCounterpart
    ): void {
        if ($counterpartBankAccountId === null) {
            if (! $requireCounterpart) {
                return;
            }

            throw ValidationException::withMessages([
                'counterpart_bank_account_id' => 'Choose the other bank account for this internal transfer.',
            ]);
        }

        if ((int) $counterpartBankAccountId === (int) $bankAccount->id) {
            throw ValidationException::withMessages([
                'counterpart_bank_account_id' => 'The counterpart account must be different from this account.',
            ]);
        }

        $counterpart = BankAccount::query()->find($counterpartBankAccountId);
        if ($counterpart === null) {
            throw ValidationException::withMessages([
                'counterpart_bank_account_id' => 'The selected counterpart bank account was not found.',
            ]);
        }

        if ($entity !== null && ! $counterpart->canUseForTransaction($entity) && ! $this->counterpartLinkedToEntity($counterpart, $entity)) {
            throw ValidationException::withMessages([
                'counterpart_bank_account_id' => 'The counterpart account is not available for this entity.',
            ]);
        }
    }

    private function counterpartLinkedToEntity(BankAccount $counterpart, BusinessEntity $entity): bool
    {
        if ((int) $counterpart->business_entity_id === (int) $entity->id) {
            return true;
        }

        return $counterpart->hasLinkOnEntity($entity);
    }
}
