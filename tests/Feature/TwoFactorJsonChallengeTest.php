<?php

use App\Http\Middleware\EnsureTwoFactorEnrolled;
use App\Http\Middleware\TwoFactorVerified;
use Tests\TestCase;

uses(TestCase::class);

it('returns json 401 with redirect when two-factor verification is required for ajax', function () {
    $verified = file_get_contents(app_path('Http/Middleware/TwoFactorVerified.php'));
    $enrolled = file_get_contents(app_path('Http/Middleware/EnsureTwoFactorEnrolled.php'));
    $challenge = file_get_contents(app_path('Http/Controllers/Auth/TwoFactorController.php'));
    $apiFetch = file_get_contents(resource_path('js/workspace-panel.js'));

    expect($verified)->toContain('expectsJson()')
        ->and($verified)->toContain('401')
        ->and($verified)->toContain('two-factor.totp-challenge')
        ->and($verified)->toContain('url.intended')
        ->and($enrolled)->toContain('expectsJson()')
        ->and($enrolled)->toContain('two-factor.setup')
        ->and($challenge)->toContain("query('return')")
        ->and($challenge)->toContain('url.intended')
        ->and($apiFetch)->toContain('response.status !== 401')
        ->and($apiFetch)->toContain('workspace_return_url')
        ->and($apiFetch)->toContain('return=');
});

it('keeps TwoFactorVerified and EnsureTwoFactorEnrolled middleware registered', function () {
    expect(class_exists(TwoFactorVerified::class))->toBeTrue()
        ->and(class_exists(EnsureTwoFactorEnrolled::class))->toBeTrue();
});
