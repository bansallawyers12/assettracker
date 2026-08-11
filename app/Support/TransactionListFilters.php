<?php

namespace App\Support;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TransactionListFilters
{
    /**
     * @return array{
     *     q: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     entity_id: ?int,
     *     type: ?string,
     *     direction: ?string,
     *     payment_status: ?string,
     *     match_status: ?string,
     *     subject_to_bas: ?string,
     *     is_flagged: ?string
     * }
     */
    public static function empty(): array
    {
        return [
            'q' => null,
            'date_from' => null,
            'date_to' => null,
            'entity_id' => null,
            'type' => null,
            'direction' => null,
            'payment_status' => null,
            'match_status' => null,
            'subject_to_bas' => null,
            'is_flagged' => null,
        ];
    }

    /**
     * @return array{
     *     q: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     entity_id: ?int,
     *     type: ?string,
     *     direction: ?string,
     *     payment_status: ?string,
     *     match_status: ?string,
     *     subject_to_bas: ?string,
     *     is_flagged: ?string
     * }
     */
    public static function fromRequest(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'entity_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', Rule::in(array_keys(Transaction::allTypes()))],
            'direction' => ['nullable', Rule::in(['income', 'expense'])],
            'payment_status' => ['nullable', Rule::in(['paid', 'unpaid'])],
            'match_status' => ['nullable', Rule::in(['matched', 'unmatched'])],
            'subject_to_bas' => ['nullable', Rule::in(['yes', 'no'])],
            'is_flagged' => ['nullable', Rule::in(['yes', 'no'])],
        ]);

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';

        return [
            'q' => $q !== '' ? $q : null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'entity_id' => isset($validated['entity_id']) ? (int) $validated['entity_id'] : null,
            'type' => $validated['type'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
            'match_status' => $validated['match_status'] ?? null,
            'subject_to_bas' => $validated['subject_to_bas'] ?? null,
            'is_flagged' => $validated['is_flagged'] ?? null,
        ];
    }

    /**
     * @param  Builder<Transaction>|Relation<Transaction>  $query
     * @param  array{
     *     q: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     entity_id: ?int,
     *     type: ?string,
     *     direction: ?string,
     *     payment_status: ?string,
     *     match_status: ?string,
     *     subject_to_bas: ?string,
     *     is_flagged: ?string
     * }  $filters
     */
    public static function apply(Builder|Relation $query, array $filters, ?int $contextEntityId = null): void
    {
        // Qualify columns so filters stay valid when callers join related tables
        // (e.g. global index sort by entity/asset — those tables also have description / business_entity_id).
        $table = self::table($query);

        if ($filters['q']) {
            $like = '%'.$filters['q'].'%';
            $query->where(function ($w) use ($like, $table) {
                $w->where($table.'.description', 'like', $like)
                    ->orWhere($table.'.comments', 'like', $like)
                    ->orWhere($table.'.invoice_number', 'like', $like)
                    ->orWhere($table.'.vendor_name', 'like', $like)
                    ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', $like))
                    ->orWhereHas('lines', fn ($lq) => $lq->where('description', 'like', $like));
            });
        }

        if ($filters['date_from']) {
            $query->whereDate($table.'.date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate($table.'.date', '<=', $filters['date_to']);
        }

        if ($contextEntityId === null && $filters['entity_id']) {
            $query->where($table.'.business_entity_id', $filters['entity_id']);
        }

        if ($filters['type']) {
            $type = $filters['type'];
            $query->where(function ($q) use ($type, $table) {
                $q->where($table.'.transaction_type', $type)
                    ->orWhereHas('lines', fn ($lq) => $lq->where('transaction_type', $type));
            });
        }

        if ($filters['direction']) {
            $typeKeys = $filters['direction'] === 'income'
                ? array_keys(Transaction::$incomeTypes)
                : array_keys(Transaction::$expenseTypes);
            // Internal transfers can be money in or out; include them in either direction filter.
            $typeKeys[] = Transaction::TYPE_INTERNAL_TRANSFER;
            $query->where(function ($q) use ($typeKeys, $table) {
                $q->whereIn($table.'.transaction_type', $typeKeys)
                    ->orWhere(function ($q2) use ($typeKeys, $table) {
                        $q2->where($table.'.transaction_type', Transaction::TYPE_SPLIT)
                            ->whereHas('lines', fn ($lq) => $lq->whereIn('transaction_type', $typeKeys));
                    });
            });
        }

        if ($filters['payment_status']) {
            $query->where($table.'.payment_status', $filters['payment_status']);
        }

        if ($filters['match_status'] === 'matched') {
            $query->whereHas('bankStatementEntries');
        } elseif ($filters['match_status'] === 'unmatched') {
            $query->whereDoesntHave('bankStatementEntries');
        }

        if ($filters['subject_to_bas']) {
            $query->where($table.'.subject_to_bas', $filters['subject_to_bas'] === 'yes');
        }

        if ($filters['is_flagged']) {
            $query->where($table.'.is_flagged', $filters['is_flagged'] === 'yes');
        }
    }

    /**
     * @param  Builder<Transaction>|Relation<Transaction>  $query
     */
    private static function table(Builder|Relation $query): string
    {
        if ($query instanceof Relation) {
            return $query->getRelated()->getTable();
        }

        return $query->getModel()->getTable();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function isActive(array $filters): bool
    {
        return collect($filters)->contains(fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array{
     *     q: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     entity_id: ?int,
     *     type: ?string,
     *     direction: ?string,
     *     payment_status: ?string,
     *     match_status: ?string,
     *     subject_to_bas: ?string,
     *     is_flagged: ?string
     * }  $filters
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function queryParams(array $filters, array $extra = [], bool $includeEntityId = true): array
    {
        $params = array_merge($extra, [
            'q' => $filters['q'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'entity_id' => $includeEntityId ? ($filters['entity_id'] ?? null) : null,
            'type' => $filters['type'] ?? null,
            'direction' => $filters['direction'] ?? null,
            'payment_status' => $filters['payment_status'] ?? null,
            'match_status' => $filters['match_status'] ?? null,
            'subject_to_bas' => $filters['subject_to_bas'] ?? null,
            'is_flagged' => $filters['is_flagged'] ?? null,
        ]);

        return array_filter(
            $params,
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
