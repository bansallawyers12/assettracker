<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Services\VendorSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function __construct(
        private readonly VendorSyncService $vendorSync
    ) {}

    public function index()
    {
        $vendors = Vendor::query()
            ->withCount('transactions')
            ->orderBy('name')
            ->get();

        $unlinkedGroups = $this->vendorSync->unlinkedVendorNameGroups();
        $referenceAreas = $this->vendorSync->referenceAreas();

        return view('vendors.index', compact('vendors', 'unlinkedGroups', 'referenceAreas'));
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());

        $vendor = Vendor::create($validated);
        $linked = $this->vendorSync->linkTransactionsMatchingName($vendor);

        $message = 'Vendor created successfully.';
        if ($linked > 0) {
            $message .= " Linked {$linked} existing transaction(s) that used this vendor name.";
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function edit(Vendor $vendor)
    {
        $vendor->loadCount('transactions');

        $usage = $this->vendorSync->usageFor($vendor);
        $recentTransactions = $vendor->transactions()
            ->with(['businessEntity', 'asset'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $referenceAreas = $this->vendorSync->referenceAreas();

        return view('vendors.edit', compact('vendor', 'usage', 'recentTransactions', 'referenceAreas'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate($this->validationRules($vendor));

        $vendor->update($validated);

        $message = 'Vendor updated successfully.';
        if ($vendor->wasChanged('name')) {
            $synced = $vendor->transactions()->count();
            if ($synced > 0) {
                $message .= " The new name is now used on {$synced} linked transaction(s) everywhere in the system.";
            }
        }

        return redirect()->route('vendors.edit', $vendor)->with('success', $message);
    }

    public function destroy(Vendor $vendor)
    {
        $linkedCount = $vendor->transactions()->count();
        $vendor->delete();

        $message = 'Vendor deleted successfully.';
        if ($linkedCount > 0) {
            $message .= " {$linkedCount} transaction(s) kept the vendor name but are no longer linked to this record.";
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function linkTransactions(Vendor $vendor)
    {
        $linked = $this->vendorSync->linkTransactionsMatchingName($vendor);
        $alsoLinkedPrevious = 0;

        if ($previous = request('previous_name')) {
            $previous = trim((string) $previous);
            if ($previous !== '' && strcasecmp($previous, $vendor->name) !== 0) {
                $alsoLinkedPrevious = $this->vendorSync->linkTransactionsMatchingName($vendor, $previous);
            }
        }

        $total = $linked + $alsoLinkedPrevious;
        $message = $total > 0
            ? "Linked {$total} transaction(s) to this vendor. Future edits here will update them automatically."
            : 'No unlinked transactions matched this vendor name.';

        return redirect()->route('vendors.edit', $vendor)->with('success', $message);
    }

    public function resolveUnlinked(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'vendor_name_label' => 'required|string|max:255',
        ]);

        $vendor = Vendor::query()->findOrFail($data['vendor_id']);
        $linked = $this->vendorSync->resolveUnlinkedGroupToVendor($vendor, $data['vendor_name_label']);

        $message = $linked > 0
            ? "Linked {$linked} transaction(s) for \"{$data['vendor_name_label']}\" to {$vendor->name}."
            : 'No matching unlinked transactions were found.';

        return redirect()->route('vendors.index')->with($linked > 0 ? 'success' : 'error', $message);
    }

    public function autoLinkAll(Request $request)
    {
        $result = $this->vendorSync->autoLinkAllExactMatches();

        if ($result['linked'] === 0) {
            return redirect()->route('vendors.index')
                ->with('error', 'No unlinked transactions were found to link.');
        }

        $message = "Auto-linked {$result['linked']} transaction(s) across {$result['vendors_touched']} vendor(s).";
        if ($result['vendors_created'] > 0) {
            $message .= " Created {$result['vendors_created']} new vendor(s).";
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function syncAllNames()
    {
        $result = $this->vendorSync->syncAllLinkedTransactionNames();

        if ($result['transactions_updated'] === 0) {
            return redirect()->route('vendors.index')
                ->with('success', 'All linked transactions already use the current vendor names.');
        }

        return redirect()->route('vendors.index')
            ->with('success', "Refreshed vendor names on {$result['transactions_updated']} transaction(s) for {$result['vendors_processed']} vendor(s).");
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(?Vendor $vendor = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vendors', 'name')->ignore($vendor?->id),
            ],
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'abn' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ];
    }
}
