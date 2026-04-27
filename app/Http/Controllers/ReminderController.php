<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\BusinessEntity;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reminder::query()
            ->with(['businessEntity', 'asset', 'user']);

        // Apply filters
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'completed') {
                $query->where('is_completed', true);
            }
        } else {
            $query->active();
        }

        if ($request->has('due')) {
            if ($request->due === 'overdue') {
                $query->overdue();
            } elseif ($request->due === 'upcoming') {
                $query->upcoming();
            } elseif (is_numeric($request->due)) {
                $query->dueWithinDays($request->due);
            }
        }

        if ($request->has('entity')) {
            $filterEntity = BusinessEntity::query()->find($request->entity);
            if ($filterEntity && $filterEntity->isOperationalEntity()) {
                $query->forBusinessEntity($request->entity);
            }
        }

        if ($request->has('asset')) {
            $query->forAsset($request->asset);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $reminders = $query->orderBy('next_due_date')->paginate(20);

        return view('reminders.index', compact('reminders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $assets = Asset::query()
            ->whereIn('business_entity_id', $businessEntities->modelKeys())
            ->with('businessEntity')
            ->get();

        $selectedEntity = null;
        $selectedAsset = null;

        if ($request->has('entity')) {
            $candidate = BusinessEntity::findOrFail($request->entity);
            $selectedEntity = $candidate->isTenancyContactOnly() ? null : $candidate;
        }

        if ($request->has('asset')) {
            $selectedAsset = Asset::findOrFail($request->asset);
            $owner = $selectedAsset->businessEntity;
            $selectedEntity = ($owner && $owner->isOperationalEntity()) ? $owner : null;
        }

        return view('reminders.create', compact('businessEntities', 'assets', 'selectedEntity', 'selectedAsset'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->mergeReminderDateFromRequest($request);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            // Date-only: allow today; dashboard sends next_due_date, standalone create uses reminder_date
            'reminder_date' => 'required|date|after_or_equal:today',
            'repeat_type' => 'required|in:none,monthly,quarterly,annual',
            'repeat_end_date' => 'nullable|date|after:reminder_date',
            'business_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'asset_id' => 'nullable|exists:assets,id',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|in:low,medium,high',
            'notes' => 'nullable|string',
        ]);

        if (empty(trim((string) ($validated['title'] ?? '')))) {
            $firstLine = (string) Str::of($validated['content'])->before("\n")->trim();
            $validated['title'] = $firstLine !== ''
                ? Str::limit($firstLine, 200)
                : 'Reminder';
        }
        $validated['priority'] = $validated['priority'] ?? 'medium';

        $reminder = new Reminder($validated);
        $reminder->user_id = Auth::id();
        $reminder->next_due_date = Carbon::parse($validated['reminder_date']);
        $reminder->save();

        if ($request->filled('business_entity_id')) {
            return redirect()->route('business-entities.show', $request->business_entity_id)
                ->with('success', 'Reminder created successfully.');
        }

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reminder $reminder)
    {
        $this->authorize('view', $reminder);
        $reminder->load(['businessEntity', 'asset', 'user']);
        return view('reminders.show', compact('reminder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reminder $reminder)
    {
        $this->authorize('update', $reminder);
        
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $assets = Asset::query()
            ->whereIn('business_entity_id', $businessEntities->modelKeys())
            ->with('businessEntity')
            ->get();

        return view('reminders.edit', compact('reminder', 'businessEntities', 'assets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reminder $reminder)
    {
        $this->authorize('update', $reminder);

        $this->mergeReminderDateFromRequest($request);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'reminder_date' => 'required|date',
            'repeat_type' => 'required|in:none,monthly,quarterly,annual',
            'repeat_end_date' => 'nullable|date|after:reminder_date',
            'business_entity_id' => ['nullable', BusinessEntity::ruleExistsOperational()],
            'asset_id' => 'nullable|exists:assets,id',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|in:low,medium,high',
            'notes' => 'nullable|string',
        ]);

        if (empty(trim((string) ($validated['title'] ?? '')))) {
            $firstLine = (string) Str::of($validated['content'])->before("\n")->trim();
            $validated['title'] = $firstLine !== ''
                ? Str::limit($firstLine, 200)
                : 'Reminder';
        }
        $validated['priority'] = $validated['priority'] ?? 'medium';

        $reminder->update($validated);
        $reminder->next_due_date = Carbon::parse($validated['reminder_date']);
        $reminder->save();

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reminder $reminder)
    {
        $this->authorize('delete', $reminder);
        $reminder->delete();

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder deleted successfully.');
    }

    /**
     * Mark a reminder as completed.
     */
    public function complete(Reminder $reminder)
    {
        $this->authorize('update', $reminder);
        $reminder->complete();

        return redirect()->back()
            ->with('success', 'Reminder marked as completed.');
    }

    /**
     * Extend a reminder's due date.
     */
    public function extend(Request $request, Reminder $reminder)
    {
        $this->authorize('update', $reminder);

        $validated = $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $reminder->extend($validated['days']);

        return redirect()->back()
            ->with('success', 'Reminder extended successfully.');
    }

    public function bulkComplete(Request $request)
    {
        $validated = $request->validate([
            'reminders' => 'required|array',
            'reminders.*' => 'exists:reminders,id'
        ]);

        $reminders = Reminder::whereIn('id', $validated['reminders'])->get();

        foreach ($reminders as $reminder) {
            $reminder->complete();
        }

        return redirect()->back()
            ->with('success', count($reminders) . ' reminders marked as completed.');
    }

    /**
     * Dashboard "Add Reminder" posts the due date as `next_due_date`; other forms use `reminder_date`.
     */
    private function mergeReminderDateFromRequest(Request $request): void
    {
        if (! $request->filled('reminder_date') && $request->filled('next_due_date')) {
            $request->merge(['reminder_date' => $request->input('next_due_date')]);
        }
    }
} 