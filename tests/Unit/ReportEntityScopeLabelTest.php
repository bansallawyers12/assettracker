<?php

namespace Tests\Unit;

use App\Models\BusinessEntity;
use App\Support\ReportEntityScopeLabel;
use PHPUnit\Framework\TestCase;

class ReportEntityScopeLabelTest extends TestCase
{
    public function test_all_scope_includes_count(): void
    {
        $entities = collect([
            ['id' => 1, 'legal_name' => 'A Pty Ltd', 'trading_name' => null],
            ['id' => 2, 'legal_name' => 'B Pty Ltd', 'trading_name' => null],
        ])->map(function ($row) {
            $e = new BusinessEntity();
            $e->forceFill($row);

            return $e;
        });

        $label = ReportEntityScopeLabel::format('all', [], $entities);

        $this->assertSame('All reporting entities (2)', $label);
    }

    public function test_selected_scope_lists_entities(): void
    {
        $entities = collect([
            ['id' => 1, 'legal_name' => 'Alpha Pty Ltd', 'trading_name' => 'Alpha'],
            ['id' => 2, 'legal_name' => 'Beta Pty Ltd', 'trading_name' => null],
        ])->map(function ($row) {
            $e = new BusinessEntity();
            $e->forceFill($row);

            return $e;
        });

        $label = ReportEntityScopeLabel::format('selected', [1, 2], $entities);

        $this->assertSame('2 entities: Alpha, Beta Pty Ltd', $label);
    }

    public function test_selected_scope_accepts_string_ids(): void
    {
        $entities = collect([
            ['id' => 5, 'legal_name' => 'Gamma Pty Ltd', 'trading_name' => 'Gamma'],
        ])->map(function ($row) {
            $e = new BusinessEntity();
            $e->forceFill($row);

            return $e;
        });

        $label = ReportEntityScopeLabel::format('selected', ['5'], $entities);

        $this->assertSame('Gamma', $label);
    }
}
