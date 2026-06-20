<?php

namespace Tests\Unit;

use App\Support\ReportEntityScopeResolver;
use PHPUnit\Framework\TestCase;

class ReportEntityScopeResolverTest extends TestCase
{
    public function test_all_scope_returns_allowed_ids(): void
    {
        $result = ReportEntityScopeResolver::resolve('all', [99], [1, 2, 3]);

        $this->assertSame([1, 2, 3], $result);
    }

    public function test_selected_scope_returns_intersection(): void
    {
        $result = ReportEntityScopeResolver::resolve('selected', [2, 3, 99], [1, 2, 3]);

        $this->assertSame([2, 3], $result);
    }

    public function test_selected_scope_with_no_ids_returns_null(): void
    {
        $this->assertNull(ReportEntityScopeResolver::resolve('selected', [], [1, 2]));
    }

    public function test_stray_entity_ids_ignored_when_scope_all(): void
    {
        $result = ReportEntityScopeResolver::resolve('all', [2], [1, 2, 3]);

        $this->assertSame([1, 2, 3], $result);
    }

    public function test_form_state_all_clears_entity_ids(): void
    {
        $state = ReportEntityScopeResolver::formState('all', [1, 2, 3]);

        $this->assertSame('all', $state['forms_scope']);
        $this->assertSame([], $state['forms_entity_ids']);
    }

    public function test_form_state_selected_preserves_ids(): void
    {
        $state = ReportEntityScopeResolver::formState('selected', [2, 5]);

        $this->assertSame('selected', $state['forms_scope']);
        $this->assertSame([2, 5], $state['forms_entity_ids']);
    }
}
