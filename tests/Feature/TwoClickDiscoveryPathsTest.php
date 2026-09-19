<?php

use Tests\TestCase;

uses(TestCase::class);

it('includes all 2-click report cards in the reports hub', function () {
    $hub = file_get_contents(resource_path('views/financial-reports/index.blade.php'));

    expect($hub)->toContain("'route' => 'financial-reports.profit-loss'")
        ->and($hub)->toContain("'route' => 'financial-reports.balance-sheet'")
        ->and($hub)->toContain("'route' => 'financial-reports.cash-flow'")
        ->and($hub)->toContain("'route' => 'financial-reports.account-transactions'")
        ->and($hub)->toContain("'route' => 'financial-reports.entity-summary'")
        ->and($hub)->toContain("'route' => 'financial-reports.journal-entries.index'")
        ->and($hub)->toContain("'route' => 'financial-reports.tracking-categories'")
        ->and($hub)->toContain("'route' => 'financial-reports.asset-summary'")
        ->and($hub)->toContain("'route' => 'financial-reports.car-register'")
        ->and($hub)->toContain("'route' => 'financial-reports.commitments'")
        ->and($hub)->toContain("'route' => 'financial-reports.compliance-gaps'")
        ->and($hub)->toContain("'route' => 'financial-reports.ato-lodgements'")
        ->and($hub)->toContain("'route' => 'portfolio.index'");
});

it('includes email sub-nav across emails area and provides drafts view', function () {
    $index = file_get_contents(resource_path('views/emails/index.blade.php'));
    $upload = file_get_contents(resource_path('views/emails/upload.blade.php'));
    $drafts = file_get_contents(resource_path('views/emails/drafts.blade.php'));
    $subnav = file_get_contents(resource_path('views/emails/partials/subnav.blade.php'));

    // Subnav component includes all 4 destinations
    expect($subnav)->toContain("route('emails.index')")
        ->and($subnav)->toContain("route('emails.drafts')")
        ->and($subnav)->toContain("route('emails.upload')")
        ->and($subnav)->toContain("route('email-templates.index')");

    // Emails index, upload, and drafts views include the subnav
    expect($index)->toContain("@include('emails.partials.subnav'")
        ->and($index)->toContain("route('emails.drafts')")
        ->and($upload)->toContain("@include('emails.partials.subnav'")
        ->and($drafts)->toContain("@include('emails.partials.subnav'")
        ->and($drafts)->toContain("route('emails.drafts.destroy'");
});

it('supports drafts HTML view and deletion in controller and routes', function () {
    $controller = file_get_contents(app_path('Http/Controllers/Email/MailMessageController.php'));
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($controller)->toContain("view('emails.drafts'")
        ->and($controller)->toContain('function destroyDraft(')
        ->and($routes)->toContain("->name('emails.drafts')")
        ->and($routes)->toContain("->name('emails.drafts.destroy')");
});

it('includes avatar dropdown destinations for admin and profile', function () {
    $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

    expect($nav)->toContain("route('admin.users.index')")
        ->and($nav)->toContain("route('admin.users.create')")
        ->and($nav)->toContain("route('profile.edit')");
});

it('includes closed entities filter link on business entities index', function () {
    $index = file_get_contents(resource_path('views/business-entities/index.blade.php'));

    expect($index)->toContain("route('business-entities.closed.index')");
});

it('links recent rows and commitments on dashboard', function () {
    $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($dashboard)->toContain("route('business-entities.show'")
        ->and($dashboard)->toContain("route('business-entities.assets.show'")
        ->and($dashboard)->toContain("route('persons.show'")
        ->and($dashboard)->toContain("route('commitments.index')");
});
