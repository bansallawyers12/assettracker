<?php

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Services\AssetMoveToTrustService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('registers the move-to-trust route and controller action', function () {
    $routes = file_get_contents(base_path('routes/web.php'));
    $controller = file_get_contents(app_path('Http/Controllers/AssetController.php'));

    expect(route('business-entities.assets.move-to-trust', [49, 11]))
        ->toContain('/business-entities/49/assets/11/move-to-trust')
        ->and(route('business-entities.assets.move-to-trust.form', [49, 11]))
        ->toContain('/business-entities/49/assets/11/move-to-trust/form')
        ->and($routes)->toContain('business-entities.assets.move-to-trust')
        ->and($routes)->toContain('moveToTrust')
        ->and($routes)->toContain('moveToTrustForm')
        ->and($controller)->toContain('function moveToTrust')
        ->and($controller)->toContain('AssetMoveToTrustService')
        ->and($controller)->toContain('ruleExistsOperationalTrust')
        ->and($controller)->toContain('moveToTrustCandidates')
        ->and($controller)->toContain('preferredMoveToTrustId')
        ->and($controller)->toContain('expectsJson()');
});

it('blocks closed and contact-only entities with actionable guidance', function () {
    $service = app(AssetMoveToTrustService::class);

    $asset = new Asset(['business_entity_id' => 1]);
    $asset->id = 10;

    $closedCompany = new BusinessEntity(['entity_type' => 'Company', 'legal_name' => 'Closed Co']);
    $closedCompany->id = 1;
    $closedCompany->closed_date = now();
    $closedCompany->exclude_from_financial_reports = false;

    $contactCompany = new BusinessEntity(['entity_type' => 'Company', 'legal_name' => 'Contact Co']);
    $contactCompany->id = 1;
    $contactCompany->closed_date = null;
    $contactCompany->exclude_from_financial_reports = true;

    $trust = new BusinessEntity(['entity_type' => 'Trust', 'legal_name' => 'Open Trust']);
    $trust->id = 3;
    $trust->closed_date = null;
    $trust->exclude_from_financial_reports = false;

    $closedTrust = new BusinessEntity(['entity_type' => 'Trust', 'legal_name' => 'Closed Trust']);
    $closedTrust->id = 4;
    $closedTrust->closed_date = now();
    $closedTrust->exclude_from_financial_reports = false;

    $openCompany = new BusinessEntity(['entity_type' => 'Company', 'legal_name' => 'Open Co']);
    $openCompany->id = 1;
    $openCompany->closed_date = null;
    $openCompany->exclude_from_financial_reports = false;

    expect(AssetMoveToTrustService::sourceBlockedMessage($closedCompany))->toContain('closed')
        ->and(AssetMoveToTrustService::sourceBlockedMessage($closedCompany))->toContain('Reopen')
        ->and(AssetMoveToTrustService::sourceBlockedMessage($contactCompany))->toContain('tenancy')
        ->and(AssetMoveToTrustService::sourceBlockedMessage($openCompany))->toBeNull()
        ->and(AssetMoveToTrustService::targetBlockedMessage($closedTrust))->toContain('closed');

    expect(fn () => $service->move($asset, $closedCompany, $trust))
        ->toThrow(ValidationException::class);

    try {
        $service->move($asset, $contactCompany, $trust);
        expect(false)->toBeTrue();
    } catch (ValidationException $exception) {
        expect($exception->errors()['target_business_entity_id'][0])->toContain('tenancy');
    }

    try {
        $service->move($asset, $openCompany, $closedTrust);
        expect(false)->toBeTrue();
    } catch (ValidationException $exception) {
        expect($exception->errors()['target_business_entity_id'][0])->toContain('Closed Trust');
    }
});

it('shows move to trust UI on company asset pages', function () {
    $show = file_get_contents(resource_path('views/assets/show.blade.php'));
    $partial = file_get_contents(resource_path('views/assets/partials/move-to-trust-form.blade.php'));
    $workspaceJs = file_get_contents(resource_path('js/asset-show-workspace.js'));
    $workspaceController = file_get_contents(app_path('Http/Controllers/AssetShowWorkspaceController.php'));

    expect($show)->toContain('Move to trust')
        ->and($show)->toContain('data-move-to-trust')
        ->and($show)->toContain('isCompany()')
        ->and($show)->toContain('assetShowPage')
        ->and($show)->toContain('data-move-to-trust-blocked')
        ->and($show)->toContain('sourceBlockedMessage')
        ->and($partial)->toContain('business-entities.assets.move-to-trust')
        ->and($partial)->toContain('target_business_entity_id')
        ->and($partial)->toContain('record correction')
        ->and($partial)->toContain('Confirm move')
        ->and($partial)->toContain('move-to-trust-ws-form')
        ->and($partial)->toContain('data-move-to-trust-eligibility-hint')
        ->and($workspaceJs)->toContain('move-to-trust/form')
        ->and($workspaceJs)->toContain('data-move-to-trust')
        ->and($workspaceJs)->toContain('payload?.message')
        ->and($workspaceController)->toContain('AssetMoveToTrustService::sourceBlockedMessage');
});

it('reparents the full cascade list inside a database transaction', function () {
    $source = file_get_contents(app_path('Services/AssetMoveToTrustService.php'));

    expect($source)->toContain('DB::transaction')
        ->and($source)->toContain('Transaction::query()')
        ->and($source)->toContain('JournalEntry::query()')
        ->and($source)->toContain('Transaction::class')
        ->and($source)->toContain('Asset::class')
        ->and($source)->toContain('Invoice::class')
        ->and($source)->toContain('Invoice::query()')
        ->and($source)->toContain('Document::query()')
        ->and($source)->toContain('DocumentCategory::query()')
        ->and($source)->toContain('ComplianceYearRecord::query()')
        ->and($source)->toContain('Note::query()')
        ->and($source)->toContain('Reminder::query()')
        ->and($source)->toContain('reminder_type')
        ->and($source)->toContain('Commitment::query()')
        ->and($source)->toContain('pruneInvalidBankLinks')
        ->and($source)->toContain('isValidForAssetRole')
        ->and($source)->toContain('rolesToDetach')
        ->and($source)->toContain('Moved from');
});

it('restricts moves to company source and trust target', function () {
    $service = app(AssetMoveToTrustService::class);

    $asset = new Asset(['business_entity_id' => 1]);
    $asset->id = 10;

    $company = new BusinessEntity(['entity_type' => 'Company', 'legal_name' => 'Co']);
    $company->id = 1;
    $company->closed_date = null;
    $company->exclude_from_financial_reports = false;

    $otherCompany = new BusinessEntity(['entity_type' => 'Company', 'legal_name' => 'Other']);
    $otherCompany->id = 2;
    $otherCompany->closed_date = null;
    $otherCompany->exclude_from_financial_reports = false;

    $trust = new BusinessEntity(['entity_type' => 'Trust', 'legal_name' => 'Trust']);
    $trust->id = 3;
    $trust->closed_date = null;
    $trust->exclude_from_financial_reports = false;

    expect(fn () => $service->move($asset, $company, $otherCompany))
        ->toThrow(ValidationException::class);

    $trustAsset = new Asset(['business_entity_id' => 3]);
    $trustAsset->id = 11;

    expect(fn () => $service->move($trustAsset, $trust, $company))
        ->toThrow(ValidationException::class);

    $wrongOwner = new Asset(['business_entity_id' => 99]);
    $wrongOwner->id = 12;

    expect(fn () => $service->move($wrongOwner, $company, $trust))
        ->toThrow(ValidationException::class);
});

it('exposes trust candidate helpers on BusinessEntity', function () {
    $model = file_get_contents(app_path('Models/BusinessEntity.php'));

    expect($model)->toContain('function trustsWhereCorporateTrustee')
        ->and($model)->toContain('function moveToTrustCandidates')
        ->and($model)->toContain('function ruleExistsOperationalTrust')
        ->and($model)->toContain('entity_trustee_id')
        ->and($model)->toContain("where('entity_type', 'Trust')")
        ->and(method_exists(BusinessEntity::class, 'trustsWhereCorporateTrustee'))->toBeTrue()
        ->and(method_exists(BusinessEntity::class, 'moveToTrustCandidates'))->toBeTrue();

    $company = new BusinessEntity(['entity_type' => 'Company']);
    $trust = new BusinessEntity(['entity_type' => 'Trust']);

    expect($company->isCompany())->toBeTrue()
        ->and($trust->isTrust())->toBeTrue()
        ->and($trust->trustsWhereCorporateTrustee())->toBeEmpty()
        ->and($trust->moveToTrustCandidates())->toBeEmpty();
});

it('keeps transaction bank_account_id intact by only updating business_entity_id on transactions', function () {
    $source = file_get_contents(app_path('Services/AssetMoveToTrustService.php'));

    expect($source)->toContain("->update(['business_entity_id' => \$targetId])")
        ->and($source)->not->toContain("'bank_account_id' => null")
        ->and($source)->toContain(Transaction::class);
});
