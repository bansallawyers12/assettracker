<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Commitment;
use App\Models\ComplianceYearRecord;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetMoveToTrustService
{
    /**
     * Reparent an asset from a trustee company onto a trust (ownership correction).
     *
     * @return array{
     *     asset: Asset,
     *     source: BusinessEntity,
     *     target: BusinessEntity,
     *     detached_bank_roles: list<string>
     * }
     */
    public function move(Asset $asset, BusinessEntity $source, BusinessEntity $target, ?int $userId = null): array
    {
        $this->assertCanMove($asset, $source, $target);

        $detachedBankRoles = [];

        DB::transaction(function () use ($asset, $source, $target, $userId, &$detachedBankRoles) {
            $assetId = (int) $asset->id;
            $targetId = (int) $target->id;

            $transactionIds = Transaction::query()
                ->where('asset_id', $assetId)
                ->pluck('id');

            Transaction::query()
                ->where('asset_id', $assetId)
                ->update(['business_entity_id' => $targetId]);

            if ($transactionIds->isNotEmpty()) {
                JournalEntry::query()
                    ->where('source_type', Transaction::class)
                    ->whereIn('source_id', $transactionIds)
                    ->update(['business_entity_id' => $targetId]);
            }

            JournalEntry::query()
                ->where('source_type', Asset::class)
                ->where('source_id', $assetId)
                ->update(['business_entity_id' => $targetId]);

            $invoiceIds = Invoice::query()
                ->where('asset_id', $assetId)
                ->pluck('id');

            Invoice::query()
                ->where('asset_id', $assetId)
                ->update(['business_entity_id' => $targetId]);

            if ($invoiceIds->isNotEmpty()) {
                JournalEntry::query()
                    ->where('source_type', Invoice::class)
                    ->whereIn('source_id', $invoiceIds)
                    ->update(['business_entity_id' => $targetId]);
            }

            Document::query()->where('asset_id', $assetId)->update(['business_entity_id' => $targetId]);
            DocumentCategory::query()->where('asset_id', $assetId)->update(['business_entity_id' => $targetId]);
            ComplianceYearRecord::query()->where('asset_id', $assetId)->update(['business_entity_id' => $targetId]);
            Note::query()->where('asset_id', $assetId)->update(['business_entity_id' => $targetId]);
            Commitment::query()->where('asset_id', $assetId)->update(['business_entity_id' => $targetId]);

            Reminder::query()
                ->where(function ($query) use ($assetId) {
                    $query->where('asset_id', $assetId)
                        ->orWhere(function ($morph) use ($assetId) {
                            $morph->where('reminder_type', Asset::class)
                                ->where('reminder_id', $assetId);
                        });
                })
                ->update(['business_entity_id' => $targetId]);

            $detachedBankRoles = $this->pruneInvalidBankLinks($asset, $target, $userId);

            $asset->update(['business_entity_id' => $targetId]);

            if ($userId !== null) {
                Note::query()->create([
                    'business_entity_id' => $targetId,
                    'asset_id' => $assetId,
                    'user_id' => $userId,
                    'content' => sprintf(
                        'Moved from %s to %s (ownership correction).',
                        $source->legal_name,
                        $target->legal_name
                    ),
                    'is_reminder' => false,
                ]);
            }
        });

        $asset->refresh();

        return [
            'asset' => $asset,
            'source' => $source,
            'target' => $target,
            'detached_bank_roles' => $detachedBankRoles,
        ];
    }

    private function assertCanMove(Asset $asset, BusinessEntity $source, BusinessEntity $target): void
    {
        if ((int) $asset->business_entity_id !== (int) $source->id) {
            throw ValidationException::withMessages([
                'target_business_entity_id' => 'This asset does not belong to the source entity.',
            ]);
        }

        if (! $source->isCompany()) {
            throw ValidationException::withMessages([
                'target_business_entity_id' => 'Assets can only be moved from a company to a trust.',
            ]);
        }

        if (! $target->isTrust()) {
            throw ValidationException::withMessages([
                'target_business_entity_id' => 'Choose a trust as the destination.',
            ]);
        }

        if ((int) $source->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'target_business_entity_id' => 'Choose a different entity as the destination.',
            ]);
        }

        if ($source->isClosed() || $target->isClosed()) {
            throw ValidationException::withMessages([
                'target_business_entity_id' => 'Cannot move an asset involving a closed entity.',
            ]);
        }

        if ($source->isTenancyContactOnly() || $target->isTenancyContactOnly()) {
            throw ValidationException::withMessages([
                'target_business_entity_id' => 'Cannot move an asset involving a non-operational contact entity.',
            ]);
        }
    }

    /**
     * Drop asset bank pivots that are not valid under the destination trust.
     *
     * @return list<string>
     */
    private function pruneInvalidBankLinks(Asset $asset, BusinessEntity $target, ?int $userId): array
    {
        $asset->load('bankAccounts');
        $rolesToDetach = [];

        foreach ($asset->bankAccounts as $account) {
            $role = (string) $account->pivot->role;
            $checkRole = $role === BankAccount::ROLE_LOAN_REPAYMENT
                ? BankAccount::ROLE_LOAN
                : $role;

            $isKnownRole = in_array($checkRole, BankAccount::ASSET_ROLES, true)
                || $role === BankAccount::ROLE_LOAN_REPAYMENT;

            if (! $isKnownRole || ! $account->isValidForAssetRole($target, $checkRole, $userId)) {
                $rolesToDetach[] = $role;
            }
        }

        $rolesToDetach = array_values(array_unique($rolesToDetach));

        foreach ($rolesToDetach as $role) {
            $asset->bankAccounts()->wherePivot('role', $role)->detach();

            // Loan slot absorbed legacy loan_repayment links
            if ($role === BankAccount::ROLE_LOAN) {
                $asset->bankAccounts()->wherePivot('role', BankAccount::ROLE_LOAN_REPAYMENT)->detach();
            }
        }

        return $rolesToDetach;
    }
}
