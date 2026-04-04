<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

        $opIds = $this->operationalBusinessEntityIds();

        $tabCounts = [
            'unpaid' => $this->transactionOperationalQuery($opIds)->where('payment_status', 'unpaid')->count(),
            'due' => $this->dueItemsTotalCount($opIds),
            'paid' => $this->transactionOperationalQuery($opIds)->where('payment_status', 'paid')->count(),
            'completed' => $this->reminderOperationalQuery($opIds)->where('is_completed', true)->count(),
        ];

        $unpaidTransactions = null;
        $paidTransactions = null;
        $completedReminders = null;
        $duePaginator = null;

        if ($tab === 'unpaid') {
            $unpaidTransactions = $this->transactionOperationalQuery($opIds)
                ->where('payment_status', 'unpaid')
                ->with(['businessEntity', 'asset'])
                ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_date')
                ->orderByDesc('date')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } elseif ($tab === 'paid') {
            $paidTransactions = $this->transactionOperationalQuery($opIds)
                ->where('payment_status', 'paid')
                ->with(['businessEntity', 'asset'])
                ->orderByRaw('CASE WHEN paid_at IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('paid_at')
                ->orderByDesc('date')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } elseif ($tab === 'completed') {
            $completedReminders = $this->reminderOperationalQuery($opIds)
                ->where('is_completed', true)
                ->with(['businessEntity', 'asset', 'user'])
                ->orderByRaw('COALESCE(completed_at, updated_at) DESC')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } else {
            $duePaginator = $this->paginatedDueItems($request, $opIds);
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

    /**
     * @param  list<int>  $opIds
     */
    private function dueItemsTotalCount(array $opIds): int
    {
        return $this->reminderOperationalQuery($opIds)->active()->whereNotNull('next_due_date')->count()
            + $this->noteReminderOperationalQuery($opIds)->whereNotNull('reminder_date')->count()
            + $this->transactionOperationalQuery($opIds)->where('payment_status', 'unpaid')->whereNotNull('due_date')->count()
            + $this->assetOperationalQuery($opIds)->whereNotNull('registration_due_date')->count()
            + $this->entityPersonOperationalQuery($opIds)->whereNotNull('asic_due_date')->count();
    }

    /**
     * @param  list<int>  $opIds
     * @return LengthAwarePaginator<int, object>
     */
    private function paginatedDueItems(Request $request, array $opIds): LengthAwarePaginator
    {
        $items = collect();

        $this->reminderOperationalQuery($opIds)
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

        $this->noteReminderOperationalQuery($opIds)
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

        $this->transactionOperationalQuery($opIds)
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

        $this->assetOperationalQuery($opIds)
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

        $this->entityPersonOperationalQuery($opIds)
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

    /**
     * @return list<int>
     */
    private function operationalBusinessEntityIds(): array
    {
        return BusinessEntity::query()->operationalEntities()->pluck('id')->all();
    }

    /**
     * @param  list<int>  $opIds
     * @return Builder<Transaction>
     */
    private function transactionOperationalQuery(array $opIds): Builder
    {
        $q = Transaction::query();
        if ($opIds === []) {
            return $q->whereRaw('0 = 1');
        }

        return $q->whereIn('business_entity_id', $opIds);
    }

    /**
     * @param  list<int>  $opIds
     * @return Builder<Reminder>
     */
    private function reminderOperationalQuery(array $opIds): Builder
    {
        $q = Reminder::query();
        if ($opIds === []) {
            return $q->whereRaw('0 = 1');
        }

        return $q->where(function (Builder $w) use ($opIds) {
            $w->whereNull('business_entity_id')
                ->orWhereIn('business_entity_id', $opIds);
        });
    }

    /**
     * @param  list<int>  $opIds
     * @return Builder<Note>
     */
    private function noteReminderOperationalQuery(array $opIds): Builder
    {
        $q = Note::query()->where('is_reminder', true);
        if ($opIds === []) {
            return $q->whereRaw('0 = 1');
        }

        return $q->where(function (Builder $w) use ($opIds) {
            $w->whereNull('business_entity_id')
                ->orWhereIn('business_entity_id', $opIds);
        });
    }

    /**
     * @param  list<int>  $opIds
     * @return Builder<Asset>
     */
    private function assetOperationalQuery(array $opIds): Builder
    {
        $q = Asset::query();
        if ($opIds === []) {
            return $q->whereRaw('0 = 1');
        }

        return $q->whereIn('business_entity_id', $opIds);
    }

    /**
     * @param  list<int>  $opIds
     * @return Builder<EntityPerson>
     */
    private function entityPersonOperationalQuery(array $opIds): Builder
    {
        $q = EntityPerson::query();
        if ($opIds === []) {
            return $q->whereRaw('0 = 1');
        }

        return $q->whereIn('business_entity_id', $opIds);
    }
}
