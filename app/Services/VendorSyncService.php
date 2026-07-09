<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Vendor;
use Illuminate\Support\Collection;

class VendorSyncService
{
    /**
     * Tables/areas that reference vendors (for display in the admin UI).
     *
     * @return list<string>
     */
    public function referenceAreas(): array
    {
        return [
            'Transactions (vendor_id + vendor_name)',
        ];
    }

    /**
     * @return array{
     *     linked_transactions: int,
     *     unlinked_matching_transactions: int,
     *     unlinked_by_previous_name: int,
     *     total_affected: int
     * }
     */
    public function usageFor(Vendor $vendor, ?string $previousName = null): array
    {
        $linked = (int) $vendor->transactions()->count();
        $unlinkedMatching = $this->unlinkedTransactionsMatchingName($vendor->name)->count();
        $unlinkedPrevious = 0;

        if ($previousName !== null && strcasecmp(trim($previousName), trim($vendor->name)) !== 0) {
            $unlinkedPrevious = $this->unlinkedTransactionsMatchingName($previousName)->count();
        }

        return [
            'linked_transactions' => $linked,
            'unlinked_matching_transactions' => $unlinkedMatching,
            'unlinked_by_previous_name' => $unlinkedPrevious,
            'total_affected' => $linked + $unlinkedMatching,
        ];
    }

    /**
     * Copy the canonical vendor name onto every transaction linked by vendor_id.
     */
    public function syncLinkedTransactionNames(Vendor $vendor): int
    {
        return Transaction::query()
            ->where('vendor_id', $vendor->id)
            ->update(['vendor_name' => $vendor->name]);
    }

    /**
     * Link transactions that only have vendor_name text (no vendor_id) when the name matches.
     */
    public function linkTransactionsMatchingName(Vendor $vendor, ?string $matchName = null): int
    {
        $label = trim((string) ($matchName ?? $vendor->name));
        if ($label === '') {
            return 0;
        }

        return $this->unlinkedTransactionsMatchingName($label)
            ->update([
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
            ]);
    }

    /**
     * Link one unlinked vendor_name group to a chosen vendor record.
     */
    public function resolveUnlinkedGroupToVendor(Vendor $vendor, string $unlinkedLabel): int
    {
        $label = trim($unlinkedLabel);
        if ($label === '') {
            return 0;
        }

        return $this->unlinkedTransactionsMatchingName($label)
            ->update([
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
            ]);
    }

    /**
     * Auto-link unlinked transactions by vendor name. Links to an existing vendor when the
     * name matches (case-insensitive), or creates a vendor record first when none exists.
     *
     * @return array{linked: int, vendors_touched: int, vendors_created: int}
     */
    public function autoLinkAllExactMatches(): array
    {
        $linked = 0;
        $vendorsTouched = 0;
        $vendorsCreated = 0;

        foreach ($this->unlinkedVendorNameGroups() as $group) {
            $label = trim((string) $group->label);
            if ($label === '') {
                continue;
            }

            $vendor = $this->findVendorByNameCaseInsensitive($label);

            if ($vendor === null) {
                $vendor = Vendor::create(['name' => $label]);
                $vendorsCreated++;
            }

            $count = $this->linkTransactionsMatchingName($vendor, $label);
            if ($count > 0) {
                $linked += $count;
                $vendorsTouched++;
            }
        }

        return [
            'linked' => $linked,
            'vendors_touched' => $vendorsTouched,
            'vendors_created' => $vendorsCreated,
        ];
    }

    /**
     * Re-sync vendor_name on all transactions that have a vendor_id.
     *
     * @return array{transactions_updated: int, vendors_processed: int}
     */
    public function syncAllLinkedTransactionNames(): array
    {
        $transactionsUpdated = 0;
        $vendorsProcessed = 0;

        foreach (Vendor::query()->has('transactions')->orderBy('name')->get() as $vendor) {
            $count = $this->syncLinkedTransactionNames($vendor);
            if ($count > 0) {
                $transactionsUpdated += $count;
                $vendorsProcessed++;
            }
        }

        return [
            'transactions_updated' => $transactionsUpdated,
            'vendors_processed' => $vendorsProcessed,
        ];
    }

    /**
     * Distinct vendor_name values on transactions with no vendor_id.
     *
     * @return Collection<int, object{label: string, transaction_count: int}>
     */
    public function unlinkedVendorNameGroups(): Collection
    {
        return Transaction::query()
            ->whereNull('vendor_id')
            ->whereNotNull('vendor_name')
            ->whereRaw("TRIM(vendor_name) <> ''")
            ->selectRaw('TRIM(vendor_name) as label, COUNT(*) as transaction_count')
            ->groupByRaw('TRIM(vendor_name)')
            ->orderByRaw('TRIM(vendor_name)')
            ->get();
    }

    private function findVendorByNameCaseInsensitive(string $name): ?Vendor
    {
        return Vendor::query()
            ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [trim($name)])
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Transaction>
     */
    private function unlinkedTransactionsMatchingName(string $name)
    {
        return Transaction::query()
            ->whereNull('vendor_id')
            ->whereNotNull('vendor_name')
            ->whereRaw('LOWER(TRIM(vendor_name)) = LOWER(TRIM(?))', [trim($name)]);
    }
}
