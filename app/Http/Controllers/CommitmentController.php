<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Commitment;
use App\Models\CommitmentPayment;
use App\Services\CommitmentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommitmentController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function __construct(
        protected CommitmentReportService $commitmentReportService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Commitment::class);

        $query = Commitment::query()
            ->with(['businessEntity', 'payments'])
            ->forOperationalEntities()
            ->orderByRaw('CASE WHEN settlement_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('settlement_date')
            ->orderBy('name');

        $status = $request->query('status', 'Active');
        if ($status !== 'all' && in_array($status, Commitment::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($request->filled('entity')) {
            $entity = BusinessEntity::query()->find($request->query('entity'));
            if ($entity && $entity->isOperationalEntity()) {
                $query->where('business_entity_id', $entity->id);
            }
        }

        if ($request->filled('type') && in_array($request->query('type'), Commitment::TYPES, true)) {
            $query->where('commitment_type', $request->query('type'));
        }

        $commitments = $query->paginate(20)->withQueryString();
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();

        return view('commitments.index', compact('commitments', 'businessEntities', 'status'));
    }

    public function create(BusinessEntity $businessEntity): View
    {
        $this->authorize('view', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorize('create', Commitment::class);

        return view('commitments.create', compact('businessEntity'));
    }

    public function store(Request $request, BusinessEntity $businessEntity): RedirectResponse
    {
        $this->authorize('view', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorize('create', Commitment::class);

        $validated = $this->validateCommitment($request);
        $validated['business_entity_id'] = $businessEntity->id;
        $validated['status'] = 'Active';

        $commitment = Commitment::create($validated);

        return redirect()
            ->route('business-entities.commitments.show', [$businessEntity, $commitment])
            ->with('success', 'Commitment created.');
    }

    public function show(BusinessEntity $businessEntity, Commitment $commitment): View
    {
        $this->authorizeCommitmentAccess($businessEntity, $commitment, 'view');

        $commitment->load(['businessEntity', 'payments', 'asset']);

        return view('commitments.show', compact('businessEntity', 'commitment'));
    }

    public function edit(BusinessEntity $businessEntity, Commitment $commitment): View|RedirectResponse
    {
        $this->authorizeCommitmentAccess($businessEntity, $commitment, 'update');

        if (! $commitment->isEditable()) {
            return redirect()
                ->route('business-entities.commitments.show', [$businessEntity, $commitment])
                ->with('error', 'Only active commitments can be edited.');
        }

        return view('commitments.edit', compact('businessEntity', 'commitment'));
    }

    public function update(Request $request, BusinessEntity $businessEntity, Commitment $commitment): RedirectResponse
    {
        $this->authorizeCommitmentAccess($businessEntity, $commitment, 'update');

        if (! $commitment->isEditable()) {
            return redirect()
                ->route('business-entities.commitments.show', [$businessEntity, $commitment])
                ->with('error', 'Only active commitments can be edited.');
        }

        $validated = $this->validateCommitment($request);
        $commitment->update($validated);

        return redirect()
            ->route('business-entities.commitments.show', [$businessEntity, $commitment])
            ->with('success', 'Commitment updated.');
    }

    public function destroy(BusinessEntity $businessEntity, Commitment $commitment): RedirectResponse
    {
        $this->authorizeCommitmentAccess($businessEntity, $commitment, 'delete');

        $commitment->delete();

        return redirect()
            ->route('commitments.index', ['entity' => $businessEntity->id])
            ->with('success', 'Commitment deleted.');
    }

    public function storePayment(Request $request, BusinessEntity $businessEntity, Commitment $commitment): RedirectResponse
    {
        $this->authorizeCommitmentAccess($businessEntity, $commitment, 'update');

        if (! $commitment->isEditable()) {
            return redirect()
                ->route('business-entities.commitments.show', [$businessEntity, $commitment])
                ->with('error', 'Payments can only be added to active commitments.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'payment_type' => 'required|in:'.implode(',', CommitmentPayment::PAYMENT_TYPES),
            'notes' => 'nullable|string',
        ]);

        $commitment->payments()->create($validated);

        return redirect()
            ->route('business-entities.commitments.show', [$businessEntity, $commitment])
            ->with('success', 'Payment recorded.');
    }

    public function destroyPayment(
        BusinessEntity $businessEntity,
        Commitment $commitment,
        CommitmentPayment $payment
    ): RedirectResponse {
        $this->authorizeCommitmentAccess($businessEntity, $commitment, 'update');

        if ((int) $payment->commitment_id !== (int) $commitment->id) {
            abort(404);
        }

        if (! $commitment->isEditable()) {
            return redirect()
                ->route('business-entities.commitments.show', [$businessEntity, $commitment])
                ->with('error', 'Payments can only be removed from active commitments.');
        }

        $payment->delete();

        return redirect()
            ->route('business-entities.commitments.show', [$businessEntity, $commitment])
            ->with('success', 'Payment removed.');
    }

    public function settle(Request $request, BusinessEntity $businessEntity, Commitment $commitment): RedirectResponse
    {
        $this->authorizeCommitmentAccess($businessEntity, $commitment, 'update');

        if ($commitment->status !== 'Active') {
            return redirect()
                ->route('business-entities.commitments.show', [$businessEntity, $commitment])
                ->with('error', 'This commitment is not active.');
        }

        $validated = $request->validate([
            'create_asset' => 'nullable|boolean',
            'asset_type' => 'nullable|in:Car,House Owned,House Rented,Warehouse,Land,Office,Shop,Real Estate,Suite',
            'acquisition_date' => 'nullable|date',
        ]);

        $createAsset = $request->boolean('create_asset');
        $asset = null;

        if ($createAsset) {
            $this->authorize('create', Asset::class);

            $settlementDate = $commitment->settlement_date ?? now();
            $asset = Asset::create([
                'business_entity_id' => $businessEntity->id,
                'user_id' => $request->user()?->id,
                'asset_type' => $validated['asset_type'] ?? $commitment->defaultAssetType(),
                'name' => $commitment->name,
                'acquisition_date' => $validated['acquisition_date']
                    ?? ($settlementDate instanceof \Carbon\CarbonInterface
                        ? $settlementDate->toDateString()
                        : $settlementDate),
                'acquisition_cost' => $commitment->contract_price,
                'current_value' => $commitment->contract_price,
                'status' => 'Active',
                'description' => $commitment->notes,
                'address' => $commitment->commitment_type === 'Property' ? $commitment->name : null,
            ]);
        }

        $commitment->update([
            'status' => 'Settled',
            'asset_id' => $asset?->id,
        ]);

        if ($asset) {
            return redirect()
                ->route('business-entities.assets.show', [$businessEntity, $asset])
                ->with('success', 'Commitment settled and asset created.');
        }

        return redirect()
            ->route('business-entities.commitments.show', [$businessEntity, $commitment])
            ->with('success', 'Commitment marked as settled.');
    }

    public function report(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Commitment::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return redirect()
                ->route('financial-reports.commitments', $request->except('entity_ids'))
                ->with('error', 'Choose at least one entity, or select “All reporting entities”.');
        }

        $status = $request->query('status', 'Active');
        if (! in_array($status, ['Active', 'Settled', 'Cancelled', 'all'], true)) {
            $status = 'Active';
        }

        $report = $this->commitmentReportService->report(
            $entityIds === [] ? null : $entityIds,
            $status
        );

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $formsScope = $request->input('scope') === 'selected' ? 'selected' : 'all';
        $formsEntityIds = $formsScope === 'selected' ? ($entityIds ?? []) : [];

        return view('financial-reports.commitments', compact(
            'report',
            'businessEntities',
            'formsScope',
            'formsEntityIds',
            'status'
        ));
    }

    /**
     * @return array<int>|null null = invalid selected scope with no entities
     */
    protected function resolveReportEntityIds(Request $request): ?array
    {
        $allowed = BusinessEntity::forFinancialReports()
            ->orderBy('legal_name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($allowed === []) {
            return [];
        }

        if ($request->input('scope') === 'selected') {
            $requested = array_values(array_unique(array_map('intval', (array) $request->input('entity_ids', []))));
            $requested = array_values(array_intersect($requested, $allowed));

            return $requested === [] ? null : $requested;
        }

        return $allowed;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateCommitment(Request $request): array
    {
        return $request->validate([
            'commitment_type' => 'required|in:'.implode(',', Commitment::TYPES),
            'name' => 'required|string|max:255',
            'contract_price' => 'required|numeric|min:0',
            'contract_date' => 'nullable|date',
            'settlement_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
    }

    protected function ensureCommitmentBelongsToEntity(BusinessEntity $businessEntity, Commitment $commitment): void
    {
        if ((int) $commitment->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }

    protected function authorizeCommitmentAccess(BusinessEntity $businessEntity, Commitment $commitment, string $ability): void
    {
        $this->ensureCommitmentBelongsToEntity($businessEntity, $commitment);
        $this->authorize('view', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorize($ability, $commitment);
    }
}
