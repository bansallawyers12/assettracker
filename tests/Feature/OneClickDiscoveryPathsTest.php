<?php

use Tests\TestCase;

uses(TestCase::class);

it('includes all 1-click destinations in top navigation', function () {
    $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

    // Top nav 1-click links
    expect($nav)->toContain("route('dashboard')")
        ->and($nav)->toContain("route('bills-tasks.index')")
        ->and($nav)->toContain("route('emails.index')")
        ->and($nav)->toContain("route('financial-reports.index')")
        ->and($nav)->toContain("route('portfolio.index')");
});

it('includes all 1-click index quick links on the dashboard', function () {
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));

    // Section 3.1: Entity / asset / person / CoA / banks / txns / vendors / invoices indexes
    expect($dashboard)->toContain("route('business-entities.index')")
        ->and($dashboard)->toContain("route('assets.index')")
        ->and($dashboard)->toContain("route('persons.index')")
        ->and($dashboard)->toContain("route('chart-of-accounts.index')")
        ->and($dashboard)->toContain("route('bank-accounts.index')")
        ->and($dashboard)->toContain("route('transactions.index')")
        ->and($dashboard)->toContain("route('vendors.index')")
        ->and($dashboard)->toContain("route('invoices.index')")
        ->and($dashboard)->toContain("route('portfolio.index')")
        ->and($dashboard)->toContain("route('financial-reports.index')");

    // Check Entities & Portfolio card specifically
    expect($dashboard)->toContain('Entities & Portfolio')
        ->and($dashboard)->toContain('Business Entities')
        ->and($dashboard)->toContain('Property Portfolio')
        ->and($dashboard)->toContain('Reports Hub');
});
