<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FinancialReportsHubPageTest extends TestCase
{
    public function test_app_layout_skips_workspace_panels_for_reports_hub(): void
    {
        $html = Blade::render('<x-app-layout :skip-workspace-panels="true"><p>Reports hub</p></x-app-layout>');

        $this->assertStringNotContainsString('id="bank-account-panel"', $html);
        $this->assertStringNotContainsString('id="entity-workspace-panel"', $html);
        $this->assertStringContainsString('Reports hub', $html);
    }

    public function test_workspace_panels_render_closed_and_inert_in_default_layout(): void
    {
        $html = Blade::render('<x-app-layout><p>Page content</p></x-app-layout>');

        $this->assertStringContainsString('id="bank-account-panel"', $html);
        $this->assertStringContainsString('data-panel-open="false"', $html);
        $this->assertStringContainsString('inert', $html);
        $this->assertStringContainsString('panel.style.pointerEvents = \'none\'', $html);
    }

    public function test_reports_hub_view_uses_direct_report_navigation_buttons(): void
    {
        $html = view('financial-reports.index', [
            'businessEntities' => collect(),
        ])->render();

        $this->assertStringContainsString('data-report-url="'.route('financial-reports.profit-loss').'"', $html);
        $this->assertStringContainsString('data-report-url="'.route('financial-reports.ato-lodgements').'"', $html);
        $this->assertStringContainsString('ATO / ASIC lodgements', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringNotContainsString('formaction=', $html);
        $this->assertStringNotContainsString('<form id="financial-reports-hub-form"', $html);
    }
}
