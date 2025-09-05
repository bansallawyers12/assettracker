<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\TrackingCategory;
use App\Models\TrackingSubCategory;
use Illuminate\Http\Request;

class TrackingSubCategoryController extends Controller
{
    public function create(BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('update', $businessEntity);
        
        return view('tracking-sub-categories.create', compact('businessEntity', 'trackingCategory'));
    }

    public function store(Request $request, BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('update', $businessEntity);
        
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
        
        return view('tracking-sub-categories.edit', compact('businessEntity', 'trackingCategory', 'trackingSubCategory'));
    }

    public function update(Request $request, BusinessEntity $businessEntity, TrackingCategory $trackingCategory, TrackingSubCategory $trackingSubCategory)
    {
        $this->authorize('update', $businessEntity);
        
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
