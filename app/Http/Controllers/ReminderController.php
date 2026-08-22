<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReminderController extends Controller
{
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
            'asset_id' => [
                'nullable',
                'exists:assets,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->filled('business_entity_id')) {
                        $asset = Asset::find($value);
                        if ($asset && (int) $asset->business_entity_id !== (int) $request->business_entity_id) {
                            $fail(__('The selected asset does not belong to the selected business entity.'));
                        }
                    }
                },
            ],
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

        return redirect()->route('bills-tasks.index')
            ->with('success', 'Reminder created successfully.');
    }

    /**
     * Display the specified reminder.
     */
    public function show(Reminder $reminder)
    {
        $this->authorize('view', $reminder);
        $reminder->load(['businessEntity', 'asset', 'user']);

        return view('reminders.show', compact('reminder'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reminder $reminder)
    {
        $this->authorize('delete', $reminder);
        $reminder->delete();

        return redirect()->route('bills-tasks.index')
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
            'reminders.*' => 'exists:reminders,id',
        ]);

        $reminders = Reminder::whereIn('id', $validated['reminders'])->get();

        $completedCount = 0;
        foreach ($reminders as $reminder) {
            if ($request->user()?->can('update', $reminder)) {
                $reminder->complete();
                $completedCount++;
            }
        }

        return redirect()->back()
            ->with('success', $completedCount.' reminders marked as completed.');
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
