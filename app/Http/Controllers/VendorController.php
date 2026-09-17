<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\Vendor;
use App\Services\VendorSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        private readonly VendorSyncService $vendorSync
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $vendors = VendorsWorkspaceController::sortedVendors($request);
        $unlinkedGroups = VendorsWorkspaceController::sortedUnlinkedGroups($request, $this->vendorSync);
        $tableSort = VendorsWorkspaceController::tableSort($request);
        $unlinkedSort = VendorsWorkspaceController::unlinkedSort($request);
        $referenceAreas = $this->vendorSync->referenceAreas();

        return view('vendors.index', compact('vendors', 'unlinkedGroups', 'referenceAreas', 'tableSort', 'unlinkedSort'));
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', BusinessEntity::class);

        return redirect()->route('vendors.index', ['panel' => 'create']);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $validated = $request->validate($this->validationRules(null, $request));
        $validated['name'] = trim($validated['name']);

        $vendor = Vendor::create($validated);
        $linked = $this->vendorSync->linkTransactionsMatchingName($vendor);

        $message = 'Vendor created successfully.';
        if ($linked > 0) {
            $message .= " Linked {$linked} existing transaction(s) that used this vendor name.";
        }

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message);
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function edit(Vendor $vendor): RedirectResponse
    {
        $this->authorize('create', BusinessEntity::class);

        return redirect()->route('vendors.index', ['panel' => 'edit', 'vendor' => $vendor->id]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $validated = $request->validate($this->validationRules($vendor, $request));
        $validated['name'] = trim($validated['name']);

        $vendor->update($validated);

        $message = 'Vendor updated successfully.';
        if ($vendor->wasChanged('name')) {
            $synced = $vendor->transactions()->count();
            if ($synced > 0) {
                $message .= " The new name is now used on {$synced} linked transaction(s) everywhere in the system.";
            }
        }

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message);
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function destroy(Request $request, Vendor $vendor): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $linkedCount = $vendor->transactions()->count();
        $vendor->delete();

        $message = 'Vendor deleted successfully.';
        if ($linkedCount > 0) {
            $message .= " {$linkedCount} transaction(s) kept the vendor name but are no longer linked to this record.";
        }

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message);
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function linkTransactions(Request $request, Vendor $vendor): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $linked = $this->vendorSync->linkTransactionsMatchingName($vendor);
        $alsoLinkedPrevious = 0;

        if ($previous = $request->input('previous_name')) {
            $previous = trim((string) $previous);
            if ($previous !== '' && strcasecmp($previous, $vendor->name) !== 0) {
                $alsoLinkedPrevious = $this->vendorSync->linkTransactionsMatchingName($vendor, $previous);
            }
        }

        $total = $linked + $alsoLinkedPrevious;
        $message = $total > 0
            ? "Linked {$total} transaction(s) to this vendor. Future edits here will update them automatically."
            : 'No unlinked transactions matched this vendor name.';

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message);
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function resolveUnlinked(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'vendor_name_label' => 'required|string|max:255',
        ]);

        $vendor = Vendor::query()->findOrFail($data['vendor_id']);
        $linked = $this->vendorSync->resolveUnlinkedGroupToVendor($vendor, $data['vendor_name_label']);

        $message = $linked > 0
            ? "Linked {$linked} transaction(s) for \"{$data['vendor_name_label']}\" to {$vendor->name}."
            : 'No matching unlinked transactions were found.';

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message, $linked > 0);
        }

        return redirect()->route('vendors.index')->with($linked > 0 ? 'success' : 'error', $message);
    }

    public function autoLinkAll(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $result = $this->vendorSync->autoLinkAllExactMatches();

        if ($result['linked'] === 0) {
            $message = 'No unlinked transactions were found to link.';

            if ($request->expectsJson()) {
                return $this->workspaceJsonResponse($request, $message, false);
            }

            return redirect()->route('vendors.index')->with('error', $message);
        }

        $message = "Auto-linked {$result['linked']} transaction(s) across {$result['vendors_touched']} vendor(s).";
        if ($result['vendors_created'] > 0) {
            $message .= " Created {$result['vendors_created']} new vendor(s).";
        }

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message);
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function syncAllNames(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $result = $this->vendorSync->syncAllLinkedTransactionNames();

        $message = $result['transactions_updated'] === 0
            ? 'All linked transactions already use the current vendor names.'
            : "Refreshed vendor names on {$result['transactions_updated']} transaction(s) for {$result['vendors_processed']} vendor(s).";

        if ($request->expectsJson()) {
            return $this->workspaceJsonResponse($request, $message);
        }

        return redirect()->route('vendors.index')->with('success', $message);
    }

    private function workspaceJsonResponse(Request $request, string $message, bool $ok = true): JsonResponse
    {
        $vendors = VendorsWorkspaceController::sortedVendors($request);
        $unlinkedGroups = VendorsWorkspaceController::sortedUnlinkedGroups($request, $this->vendorSync);

        return response()->json([
            'status' => $ok,
            'message' => $message,
            'list_html' => VendorsWorkspaceController::listHtml($vendors, $request, $unlinkedGroups),
            'unlinked_html' => VendorsWorkspaceController::unlinkedHtml($unlinkedGroups, $vendors, $request),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(?Vendor $vendor = null, ?Request $request = null): array
    {
        $inputName = trim((string) ($request?->input('name') ?? ''));

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vendors', 'name')
                    ->where(fn ($query) => $query->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$inputName]))
                    ->ignore($vendor?->id),
            ],
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'abn' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ];
    }
}
