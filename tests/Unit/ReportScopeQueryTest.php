<?php

namespace Tests\Unit;

use App\Support\ReportScopeQuery;
use PHPUnit\Framework\TestCase;

class ReportScopeQueryTest extends TestCase
{
    public function test_build_all_scope_omits_entity_ids(): void
    {
        $query = ReportScopeQuery::build('all', [1, 2], ['start_date' => '2025-01-01']);

        $this->assertSame('all', $query['scope']);
        $this->assertSame('2025-01-01', $query['start_date']);
        $this->assertArrayNotHasKey('entity_ids', $query);
    }

    public function test_build_selected_scope_includes_entity_ids(): void
    {
        $query = ReportScopeQuery::build('selected', [3, 7], ['basis' => 'cash']);

        $this->assertSame('selected', $query['scope']);
        $this->assertSame('cash', $query['basis']);
        $this->assertSame([3, 7], $query['entity_ids']);
    }
}
