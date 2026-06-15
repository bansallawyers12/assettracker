<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AssetSummaryReportService
{
    /**
     * Build the asset summary register dataset.
     *
     * @param  array<int>|null  $entityIds  null = all reporting entities
     */
    public function report(?array $entityIds = null, bool $showDisposed = false): array
    {
        $query = Asset::query()
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->whereHas('businessEntity', fn ($q) => $q->forFinancialReports())
            ->with([
                'businessEntity',
                'bankAccounts',
                'tenants' => fn ($q) => $q->orderByRaw('move_out_date IS NULL DESC')->orderBy('move_in_date', 'desc'),
                'leases'  => fn ($q) => $q->with('tenant')->orderBy('start_date', 'desc'),
            ])
            ->orderBy('name');

        if ($entityIds !== null && $entityIds !== []) {
            $query->whereIn('business_entity_id', $entityIds);
        }

        $assets = $query->get();

        // Batch-load trustee map: for each owning entity (Pty Ltd acting as trustee),
        // find the trust(s) it is trustee for via entity_person pivot.
        $owningEntityIds = $assets->pluck('business_entity_id')->filter()->unique()->values();
        $trusteeMap = $this->buildTrusteeMap($owningEntityIds);

        $active   = collect();
        $disposed = collect();

        foreach ($assets as $asset) {
            $row = $this->buildRow($asset, $trusteeMap);

            if ($row['is_disposed']) {
                $disposed->push($row);
            } else {
                $active->push($row);
            }
        }

        $activeRented  = $active->filter(fn ($r) => ! $r['is_vacant'] && ! $r['is_disposed'])->count();
        $activeVacant  = $active->filter(fn ($r) => $r['is_vacant'])->count();
        $totalLoanBalance    = $active->sum(fn ($r) => $r['loan_balance'] ?? 0);
        $totalEquityRequired = $active->sum(fn ($r) => $r['equity_required'] ?? 0);
        $totalLandTax        = $active->sum(fn ($r) => $r['land_tax_amount'] ?? 0);

        return [
            'active'   => $active->values()->all(),
            'disposed' => $disposed->values()->all(),
            'totals'   => [
                'active_count'          => $active->count(),
                'rented_count'          => $activeRented,
                'vacant_count'          => $activeVacant,
                'disposed_count'        => $disposed->count(),
                'total_loan_balance'    => $totalLoanBalance > 0 ? $totalLoanBalance : null,
                'total_equity_required' => $totalEquityRequired > 0 ? $totalEquityRequired : null,
                'total_land_tax'        => $totalLandTax > 0 ? $totalLandTax : null,
            ],
        ];
    }

    /**
     * Build a single report row from an asset.
     *
     * @param  Collection<int, \Illuminate\Support\Collection>  $trusteeMap
     * @return array<string, mixed>
     */
    private function buildRow(Asset $asset, Collection $trusteeMap): array
    {
        $entity = $asset->businessEntity;

        // Trustee label: the trust(s) this entity acts as trustee for
        $trusteeLabel = $this->resolveTrusteeLabel($asset, $trusteeMap);

        // Active tenant: no move_out_date, most recent move_in_date
        $activeTenant = $asset->tenants
            ->filter(fn ($t) => $t->move_out_date === null)
            ->sortByDesc('move_in_date')
            ->first();

        // Active lease: no end_date or end_date in future, most recent start_date
        $activeLease = $asset->leases
            ->filter(fn ($l) => $l->end_date === null || $l->end_date->gte(now()))
            ->sortByDesc('start_date')
            ->first();

        // Occupant name: prefer active lease tenant, fallback to standalone tenant
        $occupant = null;
        if ($activeLease && $activeLease->tenant) {
            $occupant = $activeLease->tenant->name;
        } elseif ($activeTenant) {
            $occupant = $activeTenant->name;
        }

        // Rent amount: prefer active lease, fallback to active tenant
        $rentAmount    = null;
        $rentFrequency = null;
        if ($activeLease) {
            $rentAmount    = $activeLease->rental_amount !== null ? (float) $activeLease->rental_amount : null;
            $rentFrequency = $activeLease->payment_frequency;
        } elseif ($activeTenant && $activeTenant->rent_amount) {
            $rentAmount    = (float) $activeTenant->rent_amount;
            $rentFrequency = $activeTenant->rent_frequency;
        }

        // Status label
        $isDisposed  = $asset->disposal_date !== null || $asset->status === 'Sold';
        $statusLabel = $this->resolveStatusLabel($asset, $activeLease, $activeTenant, $isDisposed);

        // Rent label (formatted)
        $rentLabel = $this->formatRent($rentAmount, $rentFrequency);

        // Row background class
        $isVacant = $occupant === null && ! $isDisposed;

        // Real-estate managed?
        $reManaged   = $activeTenant?->is_real_estate_managed ?? false;
        $reCompany   = $reManaged ? ($activeTenant?->realEstateCompany?->name ?? null) : null;
        $loanRepaymentAccount = $asset->bankAccountForRole(BankAccount::ROLE_LOAN_REPAYMENT);

        return [
            'asset'           => $asset,
            'entity_name'     => $entity?->legal_name ?? '',
            'trustee_label'   => $trusteeLabel,
            'status_label'    => $statusLabel,
            'occupant_label'  => $occupant ?? ($isDisposed ? '—' : 'Vacant'),
            'rent_label'      => $rentLabel,
            'rent_amount'     => $rentAmount,
            'rent_frequency'  => $rentFrequency,
            'acquisition_date'=> $asset->acquisition_date,
            'acquisition_cost'=> $asset->acquisition_cost !== null ? (float) $asset->acquisition_cost : null,
            'is_disposed'     => $isDisposed,
            'is_vacant'       => $isVacant,
            're_managed'      => $reManaged,
            're_company'      => $reCompany,
            'loan_provider'        => filled($asset->loan_provider) ? $asset->loan_provider : null,
            'loan_payment_amount'  => $asset->loan_payment_amount !== null ? (float) $asset->loan_payment_amount : null,
            'loan_balance'         => $asset->loan_balance !== null ? (float) $asset->loan_balance : null,
            'equity_required'      => $asset->equity_required !== null ? (float) $asset->equity_required : null,
            'loan_repayment_bsb'   => $loanRepaymentAccount ? BankAccount::formatBsb($loanRepaymentAccount->bsb) : null,
            'loan_repayment_account_number' => $loanRepaymentAccount?->account_number,
            'direct_debit_amount'  => $asset->direct_debit_amount !== null ? (float) $asset->direct_debit_amount : null,
            'rent_paid_by'         => filled($asset->rent_paid_by) ? $asset->rent_paid_by : null,
            'land_tax_amount'      => $asset->land_tax_amount !== null ? (float) $asset->land_tax_amount : null,
            'land_tax_due_date'    => $asset->land_tax_due_date,
            'sro_updated'          => (bool) $asset->sro_updated,
        ];
    }

    /**
     * Resolve the trustee label for a property.
     *
     * In Australian structures, a Pty Ltd can act "as trustee for" a Trust.
     * We look up the entity_person pivot: rows where entity_trustee_id = owning entity's id
     * represent trusts that the owning company is trustee of.
     *
     * @param  Collection<int, \Illuminate\Support\Collection>  $trusteeMap
     */
    private function resolveTrusteeLabel(Asset $asset, Collection $trusteeMap): string
    {
        $entityId = (int) $asset->business_entity_id;

        // Case 1: This Pty Ltd is trustee FOR one or more trusts
        if ($trusteeMap->has($entityId)) {
            $trustNames = $trusteeMap->get($entityId)
                ->map(fn ($ep) => $ep->businessEntity?->legal_name)
                ->filter()
                ->unique()
                ->join(', ');
            if ($trustNames !== '') {
                return $trustNames;
            }
        }

        // Case 2: The owning entity itself is a Trust — show its trustee names
        $entity = $asset->businessEntity;
        if ($entity && $entity->entity_type === 'Trust') {
            $names = $entity->trustees
                ->map(function ($ep) {
                    if ($ep->trusteeEntity) {
                        return $ep->trusteeEntity->legal_name;
                    }
                    if ($ep->person) {
                        return trim(($ep->person->first_name ?? '') . ' ' . ($ep->person->last_name ?? ''));
                    }
                    return null;
                })
                ->filter()
                ->join(', ');

            return $names;
        }

        return '';
    }

    /**
     * Derive a human-readable status label matching the spreadsheet format.
     */
    private function resolveStatusLabel(
        Asset $asset,
        mixed $activeLease,
        mixed $activeTenant,
        bool $isDisposed
    ): string {
        if ($isDisposed) {
            $date = $asset->disposal_date
                ? Carbon::parse($asset->disposal_date)->format('M Y')
                : null;
            return $date ? "Sold $date" : 'Sold';
        }

        // Rented from lease start date
        if ($activeLease && $activeLease->start_date) {
            return 'Rented FROM ' . $activeLease->start_date->format('M Y');
        }

        // Rented from tenant move-in date
        if ($activeTenant && $activeTenant->move_in_date) {
            return 'Rented FROM ' . $activeTenant->move_in_date->format('M Y');
        }

        return 'Active';
    }

    /**
     * Format rent as "$X,XXX / mo" (or other frequency).
     */
    private function formatRent(?float $amount, ?string $frequency): string
    {
        if ($amount === null) {
            return '—';
        }

        $freq = match (strtolower((string) $frequency)) {
            'weekly'     => 'wk',
            'fortnightly'=> 'fn',
            'monthly'    => 'mo',
            'annually'   => 'yr',
            default      => $frequency ?? 'mo',
        };

        return '$' . number_format($amount, 0) . ' / ' . $freq;
    }

    /**
     * Batch-load trust relationships.
     * Returns a Collection keyed by entity_trustee_id (the Pty Ltd ID),
     * with values being a Collection of EntityPerson records (each having businessEntity loaded = the Trust).
     *
     * @param  Collection<int, int>  $entityIds
     * @return Collection<int, Collection>
     */
    private function buildTrusteeMap(Collection $entityIds): Collection
    {
        if ($entityIds->isEmpty()) {
            return collect();
        }

        return EntityPerson::query()
            ->whereIn('entity_trustee_id', $entityIds)
            ->where('role', 'Trustee')
            ->where('role_status', 'Active')
            ->with('businessEntity')
            ->get()
            ->groupBy('entity_trustee_id');
    }
}
