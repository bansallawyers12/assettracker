<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\BusinessEntityBankAccount;
use App\Models\Commitment;
use App\Models\ComplianceYearRecord;
use App\Models\ContactList;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\EntityPerson;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Tenant;
use App\Models\TrackingCategory;
use App\Models\Transaction;
use App\Models\TransactionLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessEntityDeletionService
{
    /**
     * Records removed with the company, and links that belong to another company or asset.
     *
     * @return array{
     *     will_delete: list<array{label: string, count: int, items: list<string>}>,
     *     warnings: list<array{heading: string, effect: string, items: list<string>}>
     * }
     */
    public function preview(BusinessEntity $entity): array
    {
        $assetIds = Asset::query()->where('business_entity_id', $entity->id)->pluck('id');
        $ownedAccountIds = BankAccount::query()->where('business_entity_id', $entity->id)->pluck('id');

        return [
            'will_delete' => $this->willDelete($entity, $assetIds, $ownedAccountIds),
            'warnings' => $this->warnings($entity, $assetIds, $ownedAccountIds),
        ];
    }

    /**
     * @param  Collection<int, int>  $assetIds
     * @param  Collection<int, int>  $ownedAccountIds
     * @return list<array{label: string, count: int, items: list<string>}>
     */
    private function willDelete(BusinessEntity $entity, Collection $assetIds, Collection $ownedAccountIds): array
    {
        $assets = Asset::query()->where('business_entity_id', $entity->id)->orderBy('name')->pluck('name');
        $accounts = BankAccount::query()
            ->where('business_entity_id', $entity->id)
            ->orderBy('account_name')
            ->get(['id', 'account_name']);

        $groups = [
            $this->group('Assets', $assets->count(), $assets->all()),
            $this->group('Tenants', Tenant::query()->whereIn('asset_id', $assetIds)->count(), Tenant::query()->whereIn('asset_id', $assetIds)->orderBy('name')->pluck('name')->all()),
            $this->group('Leases', Lease::query()->whereIn('asset_id', $assetIds)->count()),
            $this->group('Invoices', Invoice::query()->where('business_entity_id', $entity->id)->count(), Invoice::query()->where('business_entity_id', $entity->id)->orderBy('invoice_number')->pluck('invoice_number')->all()),
            $this->group('Journal entries', JournalEntry::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('Bank accounts owned by this company', $accounts->count(), $accounts->map(fn (BankAccount $account): string => (string) $account->account_name)->all()),
            $this->group('Bank transactions on those accounts', Transaction::query()->whereIn('bank_account_id', $ownedAccountIds)->count()),
            $this->group('Notes', Note::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('Reminders', Reminder::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('Commitments', Commitment::query()->where('business_entity_id', $entity->id)->count(), Commitment::query()->where('business_entity_id', $entity->id)->orderBy('name')->pluck('name')->all()),
            $this->group('Documents', Document::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('Document categories', DocumentCategory::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('Compliance years', ComplianceYearRecord::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('Contacts', ContactList::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('Tracking categories', TrackingCategory::query()->where('business_entity_id', $entity->id)->count()),
            $this->group('People roles on this company', EntityPerson::query()->where('business_entity_id', $entity->id)->count()),
        ];

        return array_values(array_filter($groups));
    }

    /**
     * @param  Collection<int, int>  $assetIds
     * @param  Collection<int, int>  $ownedAccountIds
     * @return list<array{heading: string, items: list<string>}>
     */
    private function warnings(BusinessEntity $entity, Collection $assetIds, Collection $ownedAccountIds): array
    {
        $warnings = array_filter([
            $this->warning(
                'Transactions of another company on a bank account owned here',
                'These transactions will be deleted with that bank account.',
                Transaction::query()
                    ->with('businessEntity')
                    ->whereIn('bank_account_id', $ownedAccountIds)
                    ->where(function ($query) use ($entity): void {
                        $query->whereNull('business_entity_id')
                            ->orWhere('business_entity_id', '!=', $entity->id);
                    })
                    ->orderBy('date')
                    ->get()
                    ->map(function (Transaction $transaction): string {
                        $owner = $transaction->businessEntity?->legal_name ?? 'no company';

                        return trim(($transaction->description ?: $transaction->transaction_type).' — booked to '.$owner);
                    })
                    ->all()
            ),
            $this->warning(
                'Bank accounts owned here that other companies also use',
                'Deleting this company deletes those accounts.',
                BusinessEntityBankAccount::query()
                    ->with(['businessEntity', 'bankAccount'])
                    ->whereIn('bank_account_id', $ownedAccountIds)
                    ->where('business_entity_id', '!=', $entity->id)
                    ->get()
                    ->map(fn (BusinessEntityBankAccount $link): string => ($link->bankAccount?->account_name ?? 'Bank account').' is also linked to '.($link->businessEntity?->legal_name ?? 'another company'))
                    ->unique()
                    ->values()
                    ->all()
            ),
            $this->warning(
                'Transactions that stay, but lose this company',
                'These are on a bank account owned by someone else. The transaction remains and the company tag is cleared.',
                Transaction::query()
                    ->with('bankAccount.businessEntity')
                    ->where('business_entity_id', $entity->id)
                    ->when($ownedAccountIds->isNotEmpty(), fn ($query) => $query->where(function ($query) use ($ownedAccountIds): void {
                        $query->whereNull('bank_account_id')
                            ->orWhereNotIn('bank_account_id', $ownedAccountIds);
                    }))
                    ->orderBy('date')
                    ->get()
                    ->map(function (Transaction $transaction): string {
                        $account = $transaction->bankAccount;
                        $where = $account === null
                            ? 'not on a bank account'
                            : ($account->account_name.' owned by '.($account->businessEntity?->legal_name ?? 'the portfolio'));

                        return trim(($transaction->description ?: $transaction->transaction_type).' — '.$where);
                    })
                    ->all()
            ),
            $this->warning(
                'Other companies that name this company on a transaction',
                'The other company keeps the transaction. The related-company link is cleared.',
                Transaction::query()
                    ->with('businessEntity')
                    ->where('related_entity_id', $entity->id)
                    ->where(function ($query) use ($entity): void {
                        $query->whereNull('business_entity_id')
                            ->orWhere('business_entity_id', '!=', $entity->id);
                    })
                    ->orderBy('date')
                    ->get()
                    ->map(fn (Transaction $transaction): string => ($transaction->businessEntity?->legal_name ?? 'Another company').': '.($transaction->description ?: $transaction->transaction_type))
                    ->all()
            ),
            $this->warning(
                'Other companies that name this company on a transaction line',
                'The line stays. The related-company link is cleared.',
                TransactionLine::query()
                    ->with('transaction.businessEntity')
                    ->where('related_entity_id', $entity->id)
                    ->whereHas('transaction', function ($query) use ($entity): void {
                        $query->whereNull('business_entity_id')
                            ->orWhere('business_entity_id', '!=', $entity->id);
                    })
                    ->get()
                    ->map(fn (TransactionLine $line): string => ($line->transaction?->businessEntity?->legal_name ?? 'Another company').': '.($line->description ?: 'transaction line'))
                    ->all()
            ),
            $this->warning(
                'Transactions of another company tagged to an asset here',
                'The other company keeps the transaction. The asset tag is cleared.',
                Transaction::query()
                    ->with(['businessEntity', 'asset'])
                    ->whereIn('asset_id', $assetIds)
                    ->where(function ($query) use ($entity): void {
                        $query->whereNull('business_entity_id')
                            ->orWhere('business_entity_id', '!=', $entity->id);
                    })
                    ->when($ownedAccountIds->isNotEmpty(), function ($query) use ($ownedAccountIds): void {
                        $query->where(function ($query) use ($ownedAccountIds): void {
                            $query->whereNull('bank_account_id')
                                ->orWhereNotIn('bank_account_id', $ownedAccountIds);
                        });
                    })
                    ->orderBy('date')
                    ->get()
                    ->map(fn (Transaction $transaction): string => ($transaction->businessEntity?->legal_name ?? 'Another company').': '.($transaction->description ?: $transaction->transaction_type).' tagged to '.($transaction->asset?->name ?? 'an asset'))
                    ->all()
            ),
            $this->warning(
                'Invoices of another company tagged to an asset here',
                'The invoice stays. The asset tag is cleared.',
                Invoice::query()
                    ->with(['businessEntity', 'asset'])
                    ->whereIn('asset_id', $assetIds)
                    ->where('business_entity_id', '!=', $entity->id)
                    ->orderBy('invoice_number')
                    ->get()
                    ->map(fn (Invoice $invoice): string => ($invoice->businessEntity?->legal_name ?? 'Another company').': '.$invoice->invoice_number.' tagged to '.($invoice->asset?->name ?? 'an asset'))
                    ->all()
            ),
            $this->warning(
                'Commitments of another company tagged to an asset here',
                'The commitment stays. The asset tag is cleared.',
                Commitment::query()
                    ->with(['businessEntity', 'asset'])
                    ->whereIn('asset_id', $assetIds)
                    ->where('business_entity_id', '!=', $entity->id)
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Commitment $commitment): string => ($commitment->businessEntity?->legal_name ?? 'Another company').': '.$commitment->name.' tagged to '.($commitment->asset?->name ?? 'an asset'))
                    ->all()
            ),
            $this->warning(
                'Assets here linked to a bank account owned elsewhere',
                'The asset is deleted. The other bank account stays.',
                $this->foreignAssetAccountLinks($entity, $assetIds)
            ),
            $this->warning(
                'Bank accounts owned elsewhere that are linked to this company',
                'Only this company\'s link is removed. The account stays with its owner.',
                BusinessEntityBankAccount::query()
                    ->with('bankAccount.businessEntity')
                    ->where('business_entity_id', $entity->id)
                    ->whereHas('bankAccount', function ($query) use ($entity): void {
                        $query->whereNull('business_entity_id')
                            ->orWhere('business_entity_id', '!=', $entity->id);
                    })
                    ->get()
                    ->map(function (BusinessEntityBankAccount $link): string {
                        $account = $link->bankAccount;
                        $owner = $account?->businessEntity?->legal_name ?? 'the portfolio';

                        return ($account?->account_name ?? 'Bank account').' stays with '.$owner;
                    })
                    ->unique()
                    ->values()
                    ->all()
            ),
            $this->warning(
                'Other companies that list this company as holder of a bank account',
                'The account stays. The holder is cleared.',
                BankAccount::query()
                    ->with('businessEntity')
                    ->where('holder_entity_id', $entity->id)
                    ->where(function ($query) use ($entity): void {
                        $query->whereNull('business_entity_id')
                            ->orWhere('business_entity_id', '!=', $entity->id);
                    })
                    ->orderBy('account_name')
                    ->get()
                    ->map(fn (BankAccount $account): string => $account->account_name.' owned by '.($account->businessEntity?->legal_name ?? 'the portfolio'))
                    ->all()
            ),
            $this->warning(
                'Other companies that list this company as appointor',
                'Those companies stay. The appointor is cleared.',
                BusinessEntity::query()
                    ->where('appointor_entity_id', $entity->id)
                    ->orderBy('legal_name')
                    ->pluck('legal_name')
                    ->all()
            ),
            $this->warning(
                'Officer roles on other companies that point at this company',
                'That role row on the other company is removed with this company.',
                EntityPerson::query()
                    ->with(['businessEntity', 'person'])
                    ->where('business_entity_id', '!=', $entity->id)
                    ->where(function ($query) use ($entity): void {
                        $query->where('entity_trustee_id', $entity->id)
                            ->orWhere('appointor_entity_id', $entity->id);
                    })
                    ->get()
                    ->map(function (EntityPerson $role): string {
                        $person = $role->person?->displayName() ?? 'A person';

                        return $role->role.' '.$person.' on '.($role->businessEntity?->legal_name ?? 'another company');
                    })
                    ->all()
            ),
            $this->warning(
                'People who also belong to another company',
                'The person stays. Only the role on this company is removed.',
                $this->sharedPeople($entity)
            ),
            $this->sharedMailWarning($entity),
        ]);

        return array_values($warnings);
    }

    /**
     * @param  list<string>  $items
     * @return array{heading: string, effect: string, items: list<string>}|null
     */
    private function warning(string $heading, string $effect, array $items): ?array
    {
        $items = array_values(array_filter($items, fn (string $item): bool => $item !== ''));

        if ($items === []) {
            return null;
        }

        $shown = array_slice($items, 0, 25);
        if (count($items) > 25) {
            $shown[] = 'and '.(count($items) - 25).' more';
        }

        return [
            'heading' => $heading,
            'effect' => $effect,
            'items' => $shown,
        ];
    }

    /**
     * @param  list<string>  $items
     * @return array{label: string, count: int, items: list<string>}|null
     */
    private function group(string $label, int $count, array $items = []): ?array
    {
        if ($count < 1) {
            return null;
        }

        $items = array_values(array_filter($items, fn (string $item): bool => $item !== ''));
        $shown = array_slice($items, 0, 25);
        if (count($items) > 25) {
            $shown[] = 'and '.(count($items) - 25).' more';
        }

        return [
            'label' => $label,
            'count' => $count,
            'items' => $shown,
        ];
    }

    /**
     * @param  Collection<int, int>  $assetIds
     * @return list<string>
     */
    private function foreignAssetAccountLinks(BusinessEntity $entity, Collection $assetIds): array
    {
        if ($assetIds->isEmpty()) {
            return [];
        }

        return DB::table('asset_bank_account')
            ->join('assets', 'assets.id', '=', 'asset_bank_account.asset_id')
            ->join('bank_accounts', 'bank_accounts.id', '=', 'asset_bank_account.bank_account_id')
            ->leftJoin('business_entities as owners', 'owners.id', '=', 'bank_accounts.business_entity_id')
            ->whereIn('asset_bank_account.asset_id', $assetIds)
            ->where(function ($query) use ($entity): void {
                $query->whereNull('bank_accounts.business_entity_id')
                    ->orWhere('bank_accounts.business_entity_id', '!=', $entity->id);
            })
            ->orderBy('assets.name')
            ->get(['assets.name as asset_name', 'bank_accounts.account_name', 'owners.legal_name as owner_name'])
            ->map(fn (object $row): string => $row->asset_name.' uses '.$row->account_name.' owned by '.($row->owner_name ?? 'the portfolio'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function sharedPeople(BusinessEntity $entity): array
    {
        $personIds = EntityPerson::query()
            ->where('business_entity_id', $entity->id)
            ->whereNotNull('person_id')
            ->pluck('person_id');

        if ($personIds->isEmpty()) {
            return [];
        }

        return EntityPerson::query()
            ->with(['person', 'businessEntity'])
            ->whereIn('person_id', $personIds)
            ->where('business_entity_id', '!=', $entity->id)
            ->get()
            ->map(fn (EntityPerson $role): string => ($role->person?->displayName() ?? 'A person').' is also '.$role->role.' of '.($role->businessEntity?->legal_name ?? 'another company'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{heading: string, effect: string, items: list<string>}|null
     */
    private function sharedMailWarning(BusinessEntity $entity): ?array
    {
        $messageIds = DB::table('business_entity_mail_message')
            ->where('business_entity_id', $entity->id)
            ->pluck('mail_message_id');

        if ($messageIds->isEmpty()) {
            return null;
        }

        $sharedWithEntities = DB::table('business_entity_mail_message')
            ->join('business_entities', 'business_entities.id', '=', 'business_entity_mail_message.business_entity_id')
            ->join('mail_messages', 'mail_messages.id', '=', 'business_entity_mail_message.mail_message_id')
            ->whereIn('business_entity_mail_message.mail_message_id', $messageIds)
            ->where('business_entity_mail_message.business_entity_id', '!=', $entity->id)
            ->get(['mail_messages.subject', 'business_entities.legal_name']);

        $sharedWithAssets = DB::table('asset_mail_message')
            ->join('assets', 'assets.id', '=', 'asset_mail_message.asset_id')
            ->join('mail_messages', 'mail_messages.id', '=', 'asset_mail_message.mail_message_id')
            ->whereIn('asset_mail_message.mail_message_id', $messageIds)
            ->get(['mail_messages.subject', 'assets.name as asset_name']);

        $items = $sharedWithEntities
            ->map(fn (object $row): string => ($row->subject ?: 'Email').' is also filed on '.$row->legal_name)
            ->merge($sharedWithAssets->map(fn (object $row): string => ($row->subject ?: 'Email').' is also filed on asset '.$row->asset_name))
            ->unique()
            ->values()
            ->all();

        return $this->warning(
            'Emails also filed on another company or asset',
            'The email stays. Only this company\'s link is removed.',
            $items
        );
    }
}
