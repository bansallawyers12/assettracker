<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\Vendor;
use App\Services\VendorSyncService;
use App\Support\TableSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VendorsWorkspaceController extends Controller
{
    public function __construct(
        private readonly VendorSyncService $vendorSync
    ) {}

    public function workspace(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $vendors = self::sortedVendors($request);
        $unlinkedGroups = self::sortedUnlinkedGroups($request, $this->vendorSync);

        return response()->json([
            'status' => true,
            'list_html' => self::listHtml($vendors, $request, $unlinkedGroups),
            'unlinked_html' => self::unlinkedHtml($unlinkedGroups, $vendors, $request),
        ]);
    }

    public function createForm(): JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        return response()->json([
            'status' => true,
            'html' => view('vendors.partials.form', [
                'vendor' => null,
                'usage' => null,
                'recentTransactions' => collect(),
                'referenceAreas' => $this->vendorSync->referenceAreas(),
            ])->render(),
        ]);
    }

    public function editForm(Vendor $vendor): JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $vendor->loadCount('transactions');

        return response()->json([
            'status' => true,
            'html' => view('vendors.partials.form', [
                'vendor' => $vendor,
                'usage' => $this->vendorSync->usageFor($vendor),
                'recentTransactions' => $vendor->transactions()
                    ->with(['businessEntity', 'asset'])
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get(),
                'referenceAreas' => $this->vendorSync->referenceAreas(),
            ])->render(),
        ]);
    }

    public static function tableSort(Request $request): TableSort
    {
        return TableSort::resolve($request, ['name', 'email', 'phone', 'abn', 'transactions'], 'name', 'asc');
    }

    public static function unlinkedSort(Request $request): TableSort
    {
        return TableSort::resolve($request, ['label', 'count'], 'label', 'asc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Vendor>
     */
    public static function sortedVendors(Request $request)
    {
        $tableSort = self::tableSort($request);
        $query = Vendor::query()->withCount('transactions');
        $tableSort->applyToQuery($query, [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'abn' => 'abn',
            'transactions' => 'transactions_count',
        ], 'name');

        return $query->get();
    }

    /**
     * @return Collection<int, object>
     */
    public static function sortedUnlinkedGroups(Request $request, ?VendorSyncService $vendorSync = null): Collection
    {
        $vendorSync ??= app(VendorSyncService::class);
        $unlinkedSort = self::unlinkedSort($request);
        $groups = $vendorSync->unlinkedVendorNameGroups();

        return $unlinkedSort->sortCollection($groups, function ($group, string $column) {
            return match ($column) {
                'count' => (int) $group->transaction_count,
                default => $group->label,
            };
        });
    }

    public static function listHtml($vendors, ?Request $request = null, $unlinkedGroups = null): string
    {
        $request ??= request();

        return view('vendors.partials.list', [
            'vendors' => $vendors,
            'unlinkedGroups' => $unlinkedGroups ?? self::sortedUnlinkedGroups($request),
            'tableSort' => self::tableSort($request),
        ])->render();
    }

    public static function unlinkedHtml($unlinkedGroups, $vendors, ?Request $request = null): string
    {
        $request ??= request();

        return view('vendors.partials.unlinked', [
            'unlinkedGroups' => $unlinkedGroups,
            'vendors' => $vendors,
            'unlinkedSort' => self::unlinkedSort($request),
        ])->render();
    }
}
