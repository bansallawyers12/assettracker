<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class TableSort
{
    public function __construct(
        public readonly string $column,
        public readonly string $order = 'asc',
    ) {}

    /**
     * @param  list<string>  $allowedColumns
     */
    public static function resolve(
        Request $request,
        array $allowedColumns,
        string $defaultColumn = 'name',
        string $defaultOrder = 'asc',
    ): self {
        $column = (string) $request->input('sort', $defaultColumn);
        if (! in_array($column, $allowedColumns, true)) {
            $column = $defaultColumn;
        }

        $order = strtolower((string) $request->input('order', $defaultOrder));
        if (! in_array($order, ['asc', 'desc'], true)) {
            $order = $defaultOrder;
        }

        return new self($column, $order);
    }

    public function isActive(string $column): bool
    {
        return $this->column === $column;
    }

    public function nextOrder(string $column): string
    {
        return $this->isActive($column) && $this->order === 'asc' ? 'desc' : 'asc';
    }

    /**
     * @param  array<string, mixed>  $preserve
     * @return array<string, mixed>
     */
    public function linkParams(string $column, array $preserve = []): array
    {
        return array_merge($preserve, [
            'sort' => $column,
            'order' => $this->nextOrder($column),
        ]);
    }

    /**
     * @template T
     * @param  Collection<int, T>  $items
     * @param  callable(T, string): mixed  $valueResolver
     * @return Collection<int, T>
     */
    public function sortCollection(Collection $items, callable $valueResolver): Collection
    {
        $sorted = $items->sortBy(function ($item) use ($valueResolver) {
            $value = $valueResolver($item, $this->column);

            if ($value === null || $value === '') {
                return '';
            }

            if (is_numeric($value)) {
                return (float) $value;
            }

            return mb_strtolower((string) $value);
        }, SORT_NATURAL | SORT_FLAG_CASE);

        if ($this->order === 'desc') {
            $sorted = $sorted->reverse();
        }

        return $sorted->values();
    }

    /**
     * @param  array<string, string|list<string>>  $columnMap
     */
    public function applyToQuery(Builder $query, array $columnMap, ?string $fallback = null): Builder
    {
        $key = array_key_exists($this->column, $columnMap)
            ? $this->column
            : ($fallback ?? array_key_first($columnMap));

        if ($key === null) {
            return $query;
        }

        $columns = $columnMap[$key] ?? $key;
        foreach ((array) $columns as $column) {
            $query->orderBy($column, $this->order);
        }

        return $query;
    }
}
