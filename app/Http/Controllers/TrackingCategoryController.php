<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\TrackingCategory;
use App\Models\TrackingSubCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrackingCategoryController extends Controller
{
    public function index(BusinessEntity $businessEntity)
    {
        $this->authorize('view', $businessEntity);
        
        $trackingCategories = TrackingCategory::where('business_entity_id', $businessEntity->id)
            ->with('subCategories')
            ->ordered()
            ->get();
            
        return view('tracking-categories.index', compact('businessEntity', 'trackingCategories'));
    }

    public function create(BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        
        return view('tracking-categories.create', compact('businessEntity'));
    }

    public function store(Request $request, BusinessEntity $businessEntity)
    {
        $this->authorize('update', $businessEntity);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $trackingCategory = TrackingCategory::create([
            'business_entity_id' => $businessEntity->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0
        ]);

        return redirect()->route('business-entities.tracking-categories.index', $businessEntity)
            ->with('success', 'Tracking category created successfully.');
    }

    public function show(BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('view', $businessEntity);
        
        $trackingCategory->load('subCategories');
        
        return view('tracking-categories.show', compact('businessEntity', 'trackingCategory'));
    }

    public function edit(BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('update', $businessEntity);
        
        return view('tracking-categories.edit', compact('businessEntity', 'trackingCategory'));
    }

    public function update(Request $request, BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('update', $businessEntity);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $trackingCategory->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0
        ]);

        return redirect()->route('business-entities.tracking-categories.index', $businessEntity)
            ->with('success', 'Tracking category updated successfully.');
    }

    public function destroy(BusinessEntity $businessEntity, TrackingCategory $trackingCategory)
    {
        $this->authorize('update', $businessEntity);
        
        // Check if category is being used
        if ($trackingCategory->transactions()->exists() || $trackingCategory->journalLines()->exists()) {
            return redirect()->route('business-entities.tracking-categories.index', $businessEntity)
                ->with('error', 'Cannot delete tracking category that is being used in transactions.');
        }

        $trackingCategory->delete();

        return redirect()->route('business-entities.tracking-categories.index', $businessEntity)
            ->with('success', 'Tracking category deleted successfully.');
    }
}
