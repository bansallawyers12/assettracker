<?php

namespace Tests\Unit;

use App\Support\TableSort;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TableSortTest extends TestCase
{
    public function test_resolve_defaults(): void
    {
        $sort = TableSort::resolve(new Request(), ['name', 'type'], 'name', 'asc');

        $this->assertSame('name', $sort->column);
        $this->assertSame('asc', $sort->order);
    }

    public function test_resolve_invalid_column_falls_back(): void
    {
        $request = Request::create('/', 'GET', ['sort' => 'invalid', 'order' => 'sideways']);
        $sort = TableSort::resolve($request, ['name', 'type'], 'type', 'desc');

        $this->assertSame('type', $sort->column);
        $this->assertSame('desc', $sort->order);
    }

    public function test_link_params_toggle_order(): void
    {
        $sort = new TableSort('name', 'asc');

        $this->assertSame(['sort' => 'name', 'order' => 'desc'], $sort->linkParams('name'));
        $this->assertSame(['q' => 'test', 'sort' => 'type', 'order' => 'asc'], $sort->linkParams('type', ['q' => 'test']));
    }

    #[DataProvider('collectionProvider')]
    public function test_sort_collection(string $column, string $order, array $expected): void
    {
        $items = collect([
            (object) ['label' => 'Charlie'],
            (object) ['label' => 'Alpha'],
            (object) ['label' => 'Bravo'],
        ]);

        $sorted = (new TableSort($column, $order))->sortCollection($items, fn ($item) => $item->label);

        $this->assertSame($expected, $sorted->pluck('label')->all());
    }

    public function test_sort_collection_handles_numeric_values(): void
    {
        $items = collect([
            (object) ['amount' => 100],
            (object) ['amount' => 25],
            (object) ['amount' => 250],
        ]);

        $sorted = (new TableSort('amount', 'asc'))->sortCollection($items, fn ($item) => $item->amount);

        $this->assertSame([25, 100, 250], $sorted->pluck('amount')->all());
    }

    public static function collectionProvider(): array
    {
        return [
            'asc' => ['label', 'asc', ['Alpha', 'Bravo', 'Charlie']],
            'desc' => ['label', 'desc', ['Charlie', 'Bravo', 'Alpha']],
        ];
    }
}
