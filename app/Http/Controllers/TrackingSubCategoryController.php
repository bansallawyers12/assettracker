<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BusinessEntity;
use App\Models\TrackingCategory;
use App\Models\TrackingSubCategory;
use Illuminate\Http\Request;

class TrackingSubCategoryController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    protected function authorizeTrackingCategory(BusinessEntity $businessEntity, TrackingCategory $trackingCategory): void
    {
        abort_unless((int) $trackingCategory->business_entity_id === (int) $businessEntity->id, 404);
    }

    protected function authorizeTrackingSubCategory(TrackingCategory $trackingCategory, TrackingSubCategory $trackingSubCategory): void
    {
        abort_unless((int) $trackingSubCategory->tracking_category_id === (int) $trackingCategory->id, 404);
    }

    public function create(BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorizeTrackingCategory($businessEntity, $trackingCategory);

        return view('tracking-sub-categories.create', compact('businessEntity', 'trackingCategory'));
    }

    public function store(Request $request, BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorizeTrackingCategory($businessEntity, $trackingCategory);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        TrackingSubCategory::create([
            'tracking_category_id' => $trackingCategory->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0
        ]);

        return redirect()->route('business-entities.tracking-categories.show', [$businessEntity, $trackingCategory])
            ->with('success', 'Tracking sub-category created successfully.');
    }

    public function edit(BusinessEntity $businessEntity, TrackingCategory $trackingCategory, TrackingSubCategory $trackingSubCategory)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorizeTrackingCategory($businessEntity, $trackingCategory);
        $this->authorizeTrackingSubCategory($trackingCategory, $trackingSubCategory);

        return view('tracking-sub-categories.edit', compact('businessEntity', 'trackingCategory', 'trackingSubCategory'));
    }

    public function update(Request $request, BusinessEntity $businessEntity, TrackingCategory $trackingCategory, TrackingSubCategory $trackingSubCategory)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorizeTrackingCategory($businessEntity, $trackingCategory);
        $this->authorizeTrackingSubCategory($trackingCategory, $trackingSubCategory);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $trackingSubCategory->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0
        ]);

        return redirect()->route('business-entities.tracking-categories.show', [$businessEntity, $trackingCategory])
            ->with('success', 'Tracking sub-category updated successfully.');
    }

    public function destroy(BusinessEntity $businessEntity, TrackingCategory $trackingCategory, TrackingSubCategory $trackingSubCategory)
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->authorizeTrackingCategory($businessEntity, $trackingCategory);
        $this->authorizeTrackingSubCategory($trackingCategory, $trackingSubCategory);

        // Check if sub-category is being used
        if ($trackingSubCategory->transactions()->exists() || $trackingSubCategory->journalLines()->exists()) {
            return redirect()->route('business-entities.tracking-categories.show', [$businessEntity, $trackingCategory])
                ->with('error', 'Cannot delete tracking sub-category that is being used in transactions.');
        }

        $trackingSubCategory->delete();

        return redirect()->route('business-entities.tracking-categories.show', [$businessEntity, $trackingCategory])
            ->with('success', 'Tracking sub-category deleted successfully.');
    }
}
