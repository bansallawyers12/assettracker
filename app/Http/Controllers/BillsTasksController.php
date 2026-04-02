<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BillsTasksController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $this->authorize('viewAny', Reminder::class);

        $tab = $request->query('tab', 'unpaid');
        if (! in_array($tab, ['unpaid', 'due', 'paid', 'completed'], true)) {
            $tab = 'unpaid';
        }

        $tabCounts = [
            'unpaid' => Transaction::query()->where('payment_status', 'unpaid')->count(),
            'due' => $this->dueItemsTotalCount(),
            'paid' => Transaction::query()->where('payment_status', 'paid')->count(),
            'completed' => Reminder::query()->where('is_completed', true)->count(),
        ];

        $unpaidTransactions = null;
        $paidTransactions = null;
        $completedReminders = null;
        $duePaginator = null;

        if ($tab === 'unpaid') {
            $unpaidTransactions = Transaction::query()
                ->where('payment_status', 'unpaid')
                ->with(['businessEntity', 'asset'])
                ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_date')
                ->orderByDesc('date')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } elseif ($tab === 'paid') {
            $paidTransactions = Transaction::query()
                ->where('payment_status', 'paid')
                ->with(['businessEntity', 'asset'])
                ->orderByRaw('CASE WHEN paid_at IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('paid_at')
                ->orderByDesc('date')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } elseif ($tab === 'completed') {
            $completedReminders = Reminder::query()
                ->where('is_completed', true)
                ->with(['businessEntity', 'asset', 'user'])
                ->orderByRaw('COALESCE(completed_at, updated_at) DESC')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } else {
            $duePaginator = $this->paginatedDueItems($request);
        }

        return view('bills-tasks.index', compact(
            'tab',
            'tabCounts',
            'unpaidTransactions',
            'paidTransactions',
            'completedReminders',
            'duePaginator'
        ));
    }

    private function dueItemsTotalCount(): int
    {
        return Reminder::query()->active()->whereNotNull('next_due_date')->count()
            + Note::query()->where('is_reminder', true)->whereNotNull('reminder_date')->count()
            + Transaction::query()->where('payment_status', 'unpaid')->whereNotNull('due_date')->count()
            + Asset::query()->whereNotNull('registration_due_date')->count()
            + EntityPerson::query()->whereNotNull('asic_due_date')->count();
    }

    /**
     * Merges several due-date sources in PHP, then paginates. Fine for typical portfolios;
     * very large datasets may need a DB-side UNION or cursor-based approach.
     *
     * @return LengthAwarePaginator<int, object>
     */
    private function paginatedDueItems(Request $request): LengthAwarePaginator
    {
        $items = collect();

        Reminder::query()
            ->active()
            ->whereNotNull('next_due_date')
            ->with(['businessEntity', 'asset', 'user'])
            ->orderBy('next_due_date')
            ->get()
            ->each(function (Reminder $r) use ($items) {
                $items->push((object) [
                    'kind' => 'reminder',
                    'sort_date' => $r->next_due_date,
                    'reminder' => $r,
                    'note' => null,
                    'transaction' => null,
                    'asset' => null,
                    'entityPerson' => null,
                ]);
            });

        Note::query()
            ->where('is_reminder', true)
            ->whereNotNull('reminder_date')
            ->with(['businessEntity', 'asset', 'user'])
            ->orderBy('reminder_date')
            ->get()
            ->each(function (Note $n) use ($items) {
                $items->push((object) [
                    'kind' => 'note',
                    'sort_date' => $n->reminder_date,
                    'reminder' => null,
                    'note' => $n,
                    'transaction' => null,
                    'asset' => null,
                    'entityPerson' => null,
                ]);
            });

        Transaction::query()
            ->where('payment_status', 'unpaid')
            ->whereNotNull('due_date')
            ->with(['businessEntity', 'asset'])
            ->orderBy('due_date')
            ->get()
            ->each(function (Transaction $t) use ($items) {
                $items->push((object) [
                    'kind' => 'bill',
                    'sort_date' => Carbon::parse($t->due_date)->startOfDay(),
                    'reminder' => null,
                    'note' => null,
                    'transaction' => $t,
                    'asset' => null,
                    'entityPerson' => null,
                ]);
            });

        Asset::query()
            ->whereNotNull('registration_due_date')
            ->with('businessEntity')
            ->orderBy('registration_due_date')
            ->get()
            ->each(function (Asset $a) use ($items) {
                $items->push((object) [
                    'kind' => 'registration',
                    'sort_date' => $a->registration_due_date,
                    'reminder' => null,
                    'note' => null,
                    'transaction' => null,
                    'asset' => $a,
                    'entityPerson' => null,
                ]);
            });

        EntityPerson::query()
            ->whereNotNull('asic_due_date')
            ->with(['businessEntity', 'person'])
            ->orderBy('asic_due_date')
            ->get()
            ->each(function (EntityPerson $ep) use ($items) {
                $items->push((object) [
                    'kind' => 'asic',
                    'sort_date' => $ep->asic_due_date,
                    'reminder' => null,
                    'note' => null,
                    'transaction' => null,
                    'asset' => null,
                    'entityPerson' => $ep,
                ]);
            });

        $sorted = $items->sortBy(function ($row) {
            return $row->sort_date?->timestamp ?? PHP_INT_MAX;
        })->values();

        $page = max(1, (int) $request->query('page', 1));
        $total = $sorted->count();
        $slice = $sorted->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            self::PER_PAGE,
            $page,
            [
                'path' => $request->url(),
                'query' => array_merge($request->query(), ['tab' => 'due']),
            ]
        );
    }
}
