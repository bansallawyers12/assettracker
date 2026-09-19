<?php

use Tests\TestCase;

uses(TestCase::class);

it('includes all primary modules in desktop top navigation', function () {
    $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

    // Desktop nav container
    expect($nav)->toContain('{{-- Desktop nav links (sm+) --}}')
        // Core links
        ->and($nav)->toContain("route('dashboard')")
        ->and($nav)->toContain("route('bills-tasks.index')")
        ->and($nav)->toContain("route('emails.index')")
        ->and($nav)->toContain("route('financial-reports.index')")
        ->and($nav)->toContain("route('portfolio.index')")
        // Entities dropdown
        ->and($nav)->toContain("{{ __('Entities') }}")
        ->and($nav)->toContain("route('business-entities.index')")
        ->and($nav)->toContain("route('assets.index')")
        ->and($nav)->toContain("route('persons.index')")
        ->and($nav)->toContain("route('financial-reports.compliance-gaps')")
        // Accounting dropdown
        ->and($nav)->toContain("{{ __('Accounting') }}")
        ->and($nav)->toContain("route('bank-accounts.index')")
        ->and($nav)->toContain("route('transactions.index')")
        ->and($nav)->toContain("route('invoices.index')")
        ->and($nav)->toContain("route('chart-of-accounts.index')")
        ->and($nav)->toContain("route('vendors.index')")
        ->and($nav)->toContain("route('commitments.index')")
        // Admin link for primary administrators
        ->and($nav)->toContain("route('admin.users.index')");
});

it('includes categorized module sections in mobile primary navigation drawer', function () {
    $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

    $start = strpos($nav, 'id="mobile-primary-nav"');
    expect($start)->not->toBeFalse();

    $mobileNav = substr($nav, $start);

    expect($mobileNav)->toContain("{{ __('Entities & Portfolio') }}")
        ->and($mobileNav)->toContain("route('business-entities.index')")
        ->and($mobileNav)->toContain("route('assets.index')")
        ->and($mobileNav)->toContain("route('persons.index')")
        ->and($mobileNav)->toContain("route('financial-reports.compliance-gaps')")
        ->and($mobileNav)->toContain("{{ __('Accounting & Finance') }}")
        ->and($mobileNav)->toContain("route('bank-accounts.index')")
        ->and($mobileNav)->toContain("route('transactions.index')")
        ->and($mobileNav)->toContain("route('invoices.index')")
        ->and($mobileNav)->toContain("route('chart-of-accounts.index')")
        ->and($mobileNav)->toContain("route('vendors.index')")
        ->and($mobileNav)->toContain("route('commitments.index')");
});

it('supports width 56 in dropdown component and active prop in dropdown-link', function () {
    $dropdown = file_get_contents(resource_path('views/components/dropdown.blade.php'));
    $dropdownLink = file_get_contents(resource_path('views/components/dropdown-link.blade.php'));

    expect($dropdown)->toContain("'56' => 'w-56'")
        ->and($dropdownLink)->toContain("@props(['active' => false])")
        ->and($dropdownLink)->toContain('$active ?? false');
});
