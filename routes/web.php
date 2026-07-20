<?php

use App\Http\Controllers\Admin\AdminUsersWorkspaceController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetInvoiceController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BankImportController;
use App\Http\Controllers\BillsTasksController;
use App\Http\Controllers\BusinessEntityController;
use App\Http\Controllers\CarReportController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CommitmentController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ComplianceReportController;
use App\Http\Controllers\ComplianceWorkspaceController;
use App\Http\Controllers\ContactListController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentWorkspaceController;
use App\Http\Controllers\Email\GmailController;
use App\Http\Controllers\Email\MailMessageController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmailTemplatesWorkspaceController;
use App\Http\Controllers\AssetsWorkspaceController;
use App\Http\Controllers\AssetShowWorkspaceController;
use App\Http\Controllers\BankAccountPanelController;
use App\Http\Controllers\BankAccountsWorkspaceController;
use App\Http\Controllers\ContactListsWorkspaceController;
use App\Http\Controllers\EntityShowWorkspaceController;
use App\Http\Controllers\EntityPersonController;
use App\Http\Controllers\PersonsWorkspaceController;
use App\Http\Controllers\PersonsIndexWorkspaceController;
use App\Http\Controllers\PersonShowWorkspaceController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyReportController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RentInvoiceController;
use App\Http\Controllers\TrackingCategoryController;
use App\Http\Controllers\TrackingSubCategoryController;
use App\Http\Controllers\VendorController;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Gated phpinfo (dev only) — /phpinfo?token=... matching PHPINFO_ACCESS_TOKEN in .env. Leave token unset in production.
Route::get('/phpinfo', function (Request $request) {
    $expected = (string) config('app.phpinfo_access_token', '');
    $token = $request->query('token', '');
    $token = is_string($token) ? $token : '';
    if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
        abort(403, 'Forbidden. Set PHPINFO_ACCESS_TOKEN in .env and open /phpinfo?token= with that value.');
    }
    phpinfo();
    exit;
});

// -----------------------------------------------------------------------
// TOTP login challenge — no guest restriction because TwoFactorVerified
// middleware can also redirect already-authenticated users here when the
// 2fa_verified session flag is missing (e.g. after session partial expiry).
// -----------------------------------------------------------------------
Route::get('/two-factor/challenge', [TwoFactorController::class, 'showChallenge'])
    ->name('two-factor.totp-challenge');
Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])
    ->name('two-factor.totp-verify');

// -----------------------------------------------------------------------
// 2FA setup / management routes
// These are intentionally exempt from 2fa.enrolled and 2fa.verified so
// that unenrolled users can actually reach the setup page.
// backup-codes requires 2fa.verified since it shows sensitive recovery data.
// -----------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/two-factor/setup', [TwoFactorController::class, 'show'])->name('two-factor.setup');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::get('/two-factor/manage', [TwoFactorController::class, 'show'])->name('two-factor.manage');
    Route::post('/two-factor/regenerate-codes', [TwoFactorController::class, 'regenerateBackupCodes'])->name('two-factor.regenerate-codes');
});

// backup-codes requires full 2FA verification to view sensitive recovery codes
Route::middleware(['auth', '2fa.enrolled', '2fa.verified'])->group(function () {
    Route::get('/two-factor/backup-codes', [TwoFactorController::class, 'showBackupCodes'])->name('two-factor.backup-codes');
});

// -----------------------------------------------------------------------
// Fully protected routes — require auth + 2FA enrolled + 2FA verified
// -----------------------------------------------------------------------
Route::middleware(['auth', '2fa.enrolled', '2fa.verified'])->group(function () {
    Route::get('/dashboard', [BusinessEntityController::class, 'dashboard'])->name('dashboard');
    Route::get('/bills-tasks', [BillsTasksController::class, 'index'])->name('bills-tasks.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', '2fa.enrolled', '2fa.verified'])->group(function () {
    // Business Entities
    Route::get('business-entities/closed', [BusinessEntityController::class, 'closedIndex'])->name('business-entities.closed.index');
    Route::resource('business-entities', BusinessEntityController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('business-entities/{businessEntity}/close', [BusinessEntityController::class, 'close'])->name('business-entities.close');
    Route::post('business-entities/{businessEntity}/notes', [BusinessEntityController::class, 'storeNote'])->name('business-entities.notes.store');
    Route::delete('business-entities/{businessEntity}/notes/{note}', [BusinessEntityController::class, 'destroyNote'])->name('business-entities.notes.destroy');
    Route::post('business-entities/{businessEntity}/import-persons', [BusinessEntityController::class, 'importPersons'])->name('business-entities.import-persons');
    Route::post('business-entities/{businessEntity}/upload-document', [DocumentController::class, 'uploadDocument'])->name('business-entities.upload-document');
    Route::post('business-entities/{businessEntity}/document-categories', [DocumentWorkspaceController::class, 'storeCategory'])->name('entities.document-categories.store');
    Route::patch('business-entities/{businessEntity}/document-categories/{category}', [DocumentWorkspaceController::class, 'updateCategory'])->name('entities.document-categories.update');
    Route::delete('business-entities/{businessEntity}/document-categories/{category}', [DocumentWorkspaceController::class, 'destroyCategory'])->name('entities.document-categories.destroy');
    Route::post('business-entities/{businessEntity}/document-categories/{category}/slots', [DocumentWorkspaceController::class, 'storeSlot'])->name('entities.document-slots.store');
    Route::patch('business-entities/{businessEntity}/document-slots/{document}', [DocumentWorkspaceController::class, 'updateSlotLabel'])->name('entities.document-slots.update');
    Route::delete('business-entities/{businessEntity}/document-slots/{document}', [DocumentWorkspaceController::class, 'destroySlot'])->name('entities.document-slots.destroy');
    Route::post('business-entities/{businessEntity}/document-slots/{document}/clear-file', [DocumentWorkspaceController::class, 'clearFile'])->name('entities.document-slots.clear-file');
    Route::patch('business-entities/{businessEntity}/document-slots/{document}/move', [DocumentWorkspaceController::class, 'moveSlot'])->name('entities.document-slots.move');
    Route::post('business-entities/{businessEntity}/documents/bulk-upload', [DocumentController::class, 'bulkUpload'])->name('entities.documents.bulk-upload');
    Route::post('business-entities/{businessEntity}/documents/auto-match', [DocumentController::class, 'autoMatch'])->name('entities.documents.auto-match');
    Route::post('business-entities/{businessEntity}/transactions/{transaction}/match', [BusinessEntityController::class, 'matchTransaction'])->name('business-entities.transactions.match');

    // Notes
    Route::post('notes/{note}/finalize', [AssetController::class, 'finalizeNote'])->name('notes.finalize');
    Route::post('notes/{note}/extend', [AssetController::class, 'extendNote'])->name('notes.extend');

    // All assets (cross-entity; dashboard "View All")
    Route::get('/assets', [AssetController::class, 'indexAll'])->name('assets.index');

    // Future commitments
    Route::get('/commitments', [CommitmentController::class, 'index'])->name('commitments.index');
    Route::resource('business-entities.commitments', CommitmentController::class)
        ->only(['create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('business-entities/{businessEntity}/commitments/{commitment}/payments', [CommitmentController::class, 'storePayment'])
        ->name('business-entities.commitments.payments.store');
    Route::delete('business-entities/{businessEntity}/commitments/{commitment}/payments/{payment}', [CommitmentController::class, 'destroyPayment'])
        ->name('business-entities.commitments.payments.destroy');
    Route::post('business-entities/{businessEntity}/commitments/{commitment}/settle', [CommitmentController::class, 'settle'])
        ->name('business-entities.commitments.settle');

    // Assets (Nested under Business Entities)
    Route::resource('business-entities.assets', AssetController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('business-entities/{businessEntity}/assets/{asset}/finalize/{type}', [AssetController::class, 'finalizeDueDate'])->name('assets.finalize-due-date');
    Route::post('business-entities/{businessEntity}/assets/{asset}/extend/{type}', [AssetController::class, 'extendDueDate'])->name('assets.extend-due-date');

    // Asset Notes Routes
    Route::get('business-entities/{businessEntity}/assets/{asset}/notes/create', [AssetController::class, 'createNote'])->name('business-entities.assets.notes.create');
    Route::post('business-entities/{businessEntity}/assets/{asset}/notes', [AssetController::class, 'storeNote'])->name('business-entities.assets.notes.store');
    Route::delete('business-entities/{businessEntity}/assets/{asset}/notes/{note}', [AssetController::class, 'destroyNote'])->name('business-entities.assets.notes.destroy');

    // Document workspace JSON (used by JS for refresh after bulk upload / structural ops)
    Route::get('/business-entities/{businessEntity}/documents/workspace', [DocumentWorkspaceController::class, 'indexWorkspace'])->name('entities.documents.workspace');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/documents/workspace', [DocumentWorkspaceController::class, 'indexAssetWorkspace'])->name('entities.asset-documents.workspace');

    // Asset Document Routes
    Route::post('/business-entities/{businessEntity}/assets/{asset}/documents', [DocumentController::class, 'uploadAssetDocument'])->name('business-entities.assets.documents.store');
    Route::get('/business-entities/{businessEntity}/documents/{document}/content', [DocumentController::class, 'streamDocument'])
        ->name('business-entities.documents.content');

    // Compliance / FY document workspace
    Route::get('/business-entities/{businessEntity}/compliance/workspace', [ComplianceWorkspaceController::class, 'indexWorkspace'])->name('entities.compliance.workspace');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/compliance/workspace', [ComplianceWorkspaceController::class, 'indexAssetWorkspace'])->name('entities.asset-compliance.workspace');
    Route::patch('/business-entities/{businessEntity}/compliance/bas-reporting', [ComplianceWorkspaceController::class, 'updateBasReporting'])->name('entities.compliance.bas-reporting');
    Route::patch('/business-entities/{businessEntity}/compliance-years/{record}', [ComplianceWorkspaceController::class, 'updateYearNotes'])->name('entities.compliance-years.update');
    Route::post('/business-entities/{businessEntity}/compliance-years/{record}/categories', [ComplianceWorkspaceController::class, 'storeCategory'])->name('entities.compliance-categories.store');
    Route::patch('/business-entities/{businessEntity}/compliance-categories/{category}', [ComplianceWorkspaceController::class, 'updateCategory'])->name('entities.compliance-categories.update');
    Route::delete('/business-entities/{businessEntity}/compliance-categories/{category}', [ComplianceWorkspaceController::class, 'destroyCategory'])->name('entities.compliance-categories.destroy');
    Route::post('/business-entities/{businessEntity}/compliance-categories/{category}/slots', [ComplianceWorkspaceController::class, 'storeSlot'])->name('entities.compliance-slots.store');
    Route::patch('/business-entities/{businessEntity}/compliance-files/{complianceFile}', [ComplianceWorkspaceController::class, 'updateFile'])->name('entities.compliance-files.update');
    Route::patch('/business-entities/{businessEntity}/compliance-files/{complianceFile}/move', [ComplianceWorkspaceController::class, 'moveFile'])->name('entities.compliance-files.move');
    Route::delete('/business-entities/{businessEntity}/compliance-files/{complianceFile}', [ComplianceWorkspaceController::class, 'destroyFile'])->name('entities.compliance-files.destroy');
    Route::patch('/business-entities/{businessEntity}/compliance-files/{complianceFile}/status', [ComplianceWorkspaceController::class, 'updateFileStatus'])->name('entities.compliance-files.status');
    Route::post('/business-entities/{businessEntity}/compliance-years/{record}/copy-custom-rows', [ComplianceWorkspaceController::class, 'copyCustomRowsFromPrior'])->name('entities.compliance-years.copy-custom-rows');
    Route::post('/business-entities/{businessEntity}/compliance/auto-match', [ComplianceController::class, 'autoMatch'])->name('entities.compliance.auto-match');
    Route::post('/business-entities/{businessEntity}/compliance/bulk-upload', [ComplianceController::class, 'bulkUpload'])->name('entities.compliance.bulk-upload');
    Route::post('/business-entities/{businessEntity}/compliance-files/{complianceFile}/upload', [ComplianceController::class, 'upload'])->name('entities.compliance-files.upload');
    Route::post('/business-entities/{businessEntity}/compliance-files/{complianceFile}/clear', [ComplianceController::class, 'clear'])->name('entities.compliance-files.clear');
    Route::get('/business-entities/{businessEntity}/compliance-files/{complianceFile}/content', [ComplianceController::class, 'streamDocument'])->name('entities.compliance-files.content');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/compliance-files/{complianceFile}/upload', [ComplianceController::class, 'uploadAsset'])->name('entities.asset-compliance-files.upload');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/compliance-files/{complianceFile}/clear', [ComplianceController::class, 'clearAsset'])->name('entities.asset-compliance-files.clear');

    // Tenant and Lease Routes
    Route::get('/business-entities/{businessEntity}/assets/{asset}/tenants/create', [AssetController::class, 'createTenant'])->name('business-entities.assets.tenants.create');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/tenants', function ($businessEntity, $asset) {
        return redirect()->route('business-entities.assets.tenants.create', [$businessEntity, $asset]);
    })->name('business-entities.assets.tenants.index');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/tenants', [AssetController::class, 'storeTenant'])->name('business-entities.assets.tenants.store');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/tenants/{tenant}/edit', [AssetController::class, 'editTenant'])->name('business-entities.assets.tenants.edit');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/tenants/{tenant}/form/edit', [AssetShowWorkspaceController::class, 'editTenantForm'])->name('business-entities.assets.tenants.form.edit');
    Route::patch('/business-entities/{businessEntity}/assets/{asset}/tenants/{tenant}', [AssetController::class, 'updateTenant'])->name('business-entities.assets.tenants.update');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/leases/create', [AssetController::class, 'createLease'])->name('business-entities.assets.leases.create');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/leases', [AssetController::class, 'storeLease'])->name('business-entities.assets.leases.store');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/leases/{lease}/edit', [AssetController::class, 'editLease'])->name('business-entities.assets.leases.edit');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/leases/{lease}/form/edit', [AssetShowWorkspaceController::class, 'editLeaseForm'])->name('business-entities.assets.leases.form.edit');
    Route::patch('/business-entities/{businessEntity}/assets/{asset}/leases/{lease}', [AssetController::class, 'updateLease'])->name('business-entities.assets.leases.update');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/leases/sync-from-tenants', [AssetController::class, 'syncLeasesFromTenants'])->name('business-entities.assets.leases.sync-from-tenants');
    Route::delete('/business-entities/{businessEntity}/assets/{asset}/bank-account-links/{role}', [AssetController::class, 'detachBankAccountLink'])->name('business-entities.assets.bank-account-links.destroy');
    Route::post('business-entities/{businessEntity}/assets/{asset}/invoices/create-for-lease', [AssetInvoiceController::class, 'storeForLease'])->name('assets.invoices.store-for-lease');

    // Entity Persons
    Route::get('entity-persons/create/{business_entity_id}', [EntityPersonController::class, 'create'])->name('entity-persons.create');
    Route::resource('entity-persons', EntityPersonController::class)->except(['create', 'destroy']);
    Route::post('entity-persons/{entityPerson}/finalize-due-date', [EntityPersonController::class, 'finalizeDueDate'])->name('entity-persons.finalize-due-date');
    Route::post('entity-persons/{entityPerson}/extend-due-date', [EntityPersonController::class, 'extendDueDate'])->name('entity-persons.extend-due-date');
    Route::get('/business-entities/{businessEntity}/persons/workspace', [PersonsWorkspaceController::class, 'index'])->name('entities.persons.workspace');
    Route::get('/business-entities/{businessEntity}/persons/form/create', [PersonsWorkspaceController::class, 'createForm'])->name('entities.persons.form.create');
    Route::get('/business-entities/{businessEntity}/persons/{entityPerson}/form/edit', [PersonsWorkspaceController::class, 'editForm'])->name('entities.persons.form.edit');
    Route::get('/business-entities/{businessEntity}/persons/{entityPerson}/detail', [PersonsWorkspaceController::class, 'showDetail'])->name('entities.persons.detail');
    Route::get('/business-entities/{businessEntity}/assets/workspace', [AssetsWorkspaceController::class, 'index'])->name('entities.assets.workspace');
    Route::get('/business-entities/{businessEntity}/assets/form/create', [AssetsWorkspaceController::class, 'createForm'])->name('entities.assets.form.create');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/form/edit', [AssetsWorkspaceController::class, 'editForm'])->name('entities.assets.form.edit');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/detail', [AssetsWorkspaceController::class, 'showDetail'])->name('entities.assets.detail');
    Route::get('/business-entities/{businessEntity}/contact-lists/workspace', [ContactListsWorkspaceController::class, 'index'])->name('entities.contact-lists.workspace');
    Route::get('/business-entities/{businessEntity}/contact-lists/form/create', [ContactListsWorkspaceController::class, 'createForm'])->name('entities.contact-lists.form.create');
    Route::get('/business-entities/{businessEntity}/contact-lists/{contactList}/form/edit', [ContactListsWorkspaceController::class, 'editForm'])->name('entities.contact-lists.form.edit');
    Route::get('/business-entities/{businessEntity}/profile/form', [EntityShowWorkspaceController::class, 'profileForm'])->name('entities.profile.form');

    Route::get('/business-entities/{businessEntity}/bank-accounts/workspace', [BankAccountsWorkspaceController::class, 'index'])->name('entities.bank-accounts.workspace');
    Route::get('/business-entities/{businessEntity}/bank-accounts/attach-form', [BankAccountsWorkspaceController::class, 'attachForm'])->name('entities.bank-accounts.attach-form');
    Route::get('/business-entities/{businessEntity}/bank-accounts/form/create', [BankAccountsWorkspaceController::class, 'createForm'])->name('entities.bank-accounts.form.create');
    Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/form/edit', [BankAccountsWorkspaceController::class, 'editForm'])->name('entities.bank-accounts.form.edit');
    Route::get('/business-entities/{businessEntity}/bank-account-links/{bankAccountLink}/rent-assets-form', [BankAccountsWorkspaceController::class, 'rentAssetsForm'])->name('entities.bank-account-links.rent-assets-form');

    // Person routes
    Route::get('persons', [EntityPersonController::class, 'indexPersons'])->name('persons.index');
    Route::get('/persons/workspace', [PersonsIndexWorkspaceController::class, 'workspace'])->name('persons.workspace');
    Route::get('/persons/form/create', [PersonsIndexWorkspaceController::class, 'createForm'])->name('persons.form.create');
    Route::get('persons/create', [EntityPersonController::class, 'createPerson'])->name('persons.create');
    Route::post('persons', [EntityPersonController::class, 'storePerson'])->name('persons.store');
    Route::get('persons/{person}', [EntityPersonController::class, 'showPerson'])->name('persons.show');
    Route::get('/persons/{person}/workspace/roles', [PersonShowWorkspaceController::class, 'roles'])->name('persons.workspace.roles');
    Route::get('/persons/{person}/roles/form/create', [PersonShowWorkspaceController::class, 'entityPicker'])->name('persons.roles.form.create');
    Route::get('/persons/{person}/bank-accounts/workspace', [PersonShowWorkspaceController::class, 'bankAccounts'])->name('persons.bank-accounts.workspace');
    Route::get('/persons/{person}/bank-accounts/form/create', [PersonShowWorkspaceController::class, 'createBankAccountForm'])->name('persons.bank-accounts.form.create');
    Route::get('/persons/{person}/bank-accounts/{bankAccount}/form/edit', [PersonShowWorkspaceController::class, 'editBankAccountForm'])->name('persons.bank-accounts.form.edit');

    // Bank Accounts
    Route::get('/business-entities/{businessEntity}/bank-accounts/create', [BusinessEntityController::class, 'createBankAccount'])->name('business-entities.bank-accounts.create');
    Route::get('/business-entities/{businessEntity}/bank-accounts', function ($businessEntity) {
        return redirect()->route('business-entities.show', $businessEntity)->withFragment('tab_bank_accounts');
    });
    Route::post('/business-entities/{businessEntity}/bank-accounts', [BusinessEntityController::class, 'storeBankAccount'])->name('business-entities.bank-accounts.store');
    Route::post('/business-entities/{businessEntity}/bank-accounts/assign', [BusinessEntityController::class, 'assignBankAccountToEntity'])->name('business-entities.bank-accounts.assign');
    Route::post('/business-entities/{businessEntity}/bank-account-links/{bankAccountLink}/rent-assets', [BusinessEntityController::class, 'syncRentCollectionAssets'])->name('business-entities.bank-account-links.rent-assets');
    Route::delete('/business-entities/{businessEntity}/bank-account-links/{bankAccountLink}', [BusinessEntityController::class, 'detachBankAccountLink'])->name('business-entities.bank-account-links.destroy');
    Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/edit', [BusinessEntityController::class, 'editBankAccount'])->name('business-entities.bank-accounts.edit');
    Route::put('/business-entities/{businessEntity}/bank-accounts/{bankAccount}', [BusinessEntityController::class, 'updateBankAccount'])->name('business-entities.bank-accounts.update');
    Route::delete('/business-entities/{businessEntity}/bank-accounts/{bankAccount}', [BusinessEntityController::class, 'destroyBankAccount'])->name('business-entities.bank-accounts.destroy');

    // Transaction Routes
    Route::post('business-entities/{businessEntity}/transactions', [BusinessEntityController::class, 'storeTransaction'])->name('business-entities.transactions.store');
    Route::get('business-entities/{businessEntity}/transactions/{transaction}/edit', [BusinessEntityController::class, 'editTransaction'])->name('business-entities.transactions.edit');
    Route::put('business-entities/{businessEntity}/transactions/{transaction}', [BusinessEntityController::class, 'updateTransaction'])->name('business-entities.transactions.update');
    Route::delete('business-entities/{businessEntity}/transactions/{transaction}', [BusinessEntityController::class, 'destroyTransaction'])->name('business-entities.transactions.destroy');

    // Existing Bank Account Transaction Routes
    Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions/create', [BusinessEntityController::class, 'createTransaction'])->name('business-entities.bank-accounts.transactions.create');
    Route::post('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions', [BusinessEntityController::class, 'storeBankTransaction'])->name('business-entities.bank-accounts.transactions.store');
    Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions/{transaction}', [BusinessEntityController::class, 'showTransaction'])->name('business-entities.bank-accounts.transactions.show');
    Route::put('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions/{transaction}', [BusinessEntityController::class, 'updateBankTransaction'])->name('business-entities.bank-accounts.transactions.update');
    Route::post('/business-entities/{businessEntity}/bank-accounts/{bankStatementEntry}/match-transaction', [BusinessEntityController::class, 'matchTransaction'])->name('business-entities.bank-accounts.match-transaction');

    // API for Bank Accounts
    Route::get('/api/business-entities/{businessEntity}/bank-accounts', [BusinessEntityController::class, 'getBankAccounts'])->name('business-entities.bank-accounts.api');

    // Bank Import Routes
    Route::post('/business-entities/{businessEntity}/bank-import/process', [BankImportController::class, 'process'])->name('business-entities.bank-import.process');
    Route::get('/business-entities/{businessEntity}/bank-import/entries', [BankImportController::class, 'entries'])->name('business-entities.bank-import.entries');
    Route::post('/business-entities/{businessEntity}/bank-import/save-matches', [BankImportController::class, 'saveMatches'])->name('business-entities.bank-import.save-matches');

    // Chart of Accounts JSON (global list; entity in legacy URL is ignored)
    Route::get('/api/chart-of-accounts', [ChartOfAccountController::class, 'apiIndex'])->name('chart-of-accounts.api');
    Route::get('/api/business-entities/{businessEntity}/chart-of-accounts', [ChartOfAccountController::class, 'getAccountsJson'])->name('business-entities.chart-of-accounts.api');

    // Reminder routes
    Route::resource('reminders', ReminderController::class);
    Route::post('reminders/{reminder}/complete', [ReminderController::class, 'complete'])->name('reminders.complete');
    Route::post('reminders/{reminder}/extend', [ReminderController::class, 'extend'])->name('reminders.extend');
    Route::post('reminders/bulk-complete', [ReminderController::class, 'bulkComplete'])->name('reminders.bulk-complete');

    // Contact List Routes
    Route::resource('business-entities.contact-lists', ContactListController::class);

    // Email Section — static paths before /emails/{id}
    Route::get('/emails', [MailMessageController::class, 'index'])->name('emails.index');
    Route::get('/emails/upload', [MailMessageController::class, 'uploadIndex'])->name('emails.upload');
    Route::post('/emails/upload', [MailMessageController::class, 'uploadMsg'])->name('emails.upload.store');
    Route::get('/emails/drafts', [MailMessageController::class, 'drafts'])->name('emails.drafts');
    Route::post('/emails/send', [MailMessageController::class, 'sendEmail'])->name('emails.send');
    Route::post('/emails/save-draft', [MailMessageController::class, 'saveDraft'])->name('emails.save-draft');
    Route::get('/emails-sync', [GmailController::class, 'sync'])->name('emails.sync');
    Route::get('/emails/{id}', [MailMessageController::class, 'show'])->whereNumber('id')->name('emails.show');
    Route::get('/emails/{id}/reply', [MailMessageController::class, 'reply'])->whereNumber('id')->name('emails.reply');
    Route::get('/emails/{id}/reply-data', [MailMessageController::class, 'getReplyData'])->whereNumber('id')->name('emails.reply-data');
    Route::post('/emails/{id}/allocate-entity', [MailMessageController::class, 'allocateToBusinessEntity'])->whereNumber('id')->name('emails.allocate.entity');
    Route::post('/emails/{id}/allocate-asset', [MailMessageController::class, 'allocateToAsset'])->whereNumber('id')->name('emails.allocate.asset');

    // Email Template Management Routes
    Route::get('/email-templates/workspace', [EmailTemplatesWorkspaceController::class, 'workspace'])->name('email-templates.workspace');
    Route::get('/email-templates/form/create', [EmailTemplatesWorkspaceController::class, 'createForm'])->name('email-templates.form.create');
    Route::get('/email-templates/{emailTemplate}/form/edit', [EmailTemplatesWorkspaceController::class, 'editForm'])->name('email-templates.form.edit');
    Route::resource('email-templates', EmailTemplateController::class);
    Route::get('/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::get('/email-templates-api/templates', [EmailTemplateController::class, 'getTemplates'])->name('email-templates.api');

    // Email Routes (Nested under Business Entities)
    Route::get('business-entities/{businessEntity}/compose-email-data', [BusinessEntityController::class, 'getComposeEmailData'])->name('business-entities.compose-email-data');
    Route::post('business-entities/{businessEntity}/send-email', [BusinessEntityController::class, 'sendEmail'])->name('business-entities.send-email');

    // Accounting System Routes — chart of accounts is global; legacy nested URLs redirect
    Route::get('/business-entities/{businessEntity}/chart-of-accounts', function () {
        return redirect()->route('chart-of-accounts.index');
    })->name('business-entities.chart-of-accounts.index');
    Route::get('/business-entities/{businessEntity}/chart-of-accounts/create', function () {
        return redirect()->route('chart-of-accounts.create');
    })->name('business-entities.chart-of-accounts.create');
    Route::get('/business-entities/{businessEntity}/chart-of-accounts/{chartOfAccount}/edit', function (ChartOfAccount $chartOfAccount) {
        return redirect()->route('chart-of-accounts.edit', $chartOfAccount);
    })->name('business-entities.chart-of-accounts.edit');
    // Legacy nested write routes (same controller; entity segment ignored — chart is global)
    Route::post('/business-entities/{businessEntity}/chart-of-accounts', [ChartOfAccountController::class, 'store'])->name('business-entities.chart-of-accounts.store');
    Route::match(['put', 'patch'], '/business-entities/{businessEntity}/chart-of-accounts/{chart_of_account}', [ChartOfAccountController::class, 'update'])->name('business-entities.chart-of-accounts.update');
    Route::delete('/business-entities/{businessEntity}/chart-of-accounts/{chart_of_account}', [ChartOfAccountController::class, 'destroy'])->name('business-entities.chart-of-accounts.destroy');
    Route::get('business-entities/{businessEntity}/financial-reports/account-transactions', [FinancialReportController::class, 'accountTransactions'])->name('business-entities.financial-reports.account-transactions');
    Route::get('business-entities/{businessEntity}/financial-reports/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('business-entities.financial-reports.profit-loss');
    Route::get('business-entities/{businessEntity}/financial-reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('business-entities.financial-reports.balance-sheet');
    Route::get('business-entities/{businessEntity}/financial-reports/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('business-entities.financial-reports.cash-flow');
    Route::get('business-entities/{businessEntity}/financial-reports/tracking-categories', [FinancialReportController::class, 'trackingCategories'])->name('business-entities.financial-reports.tracking-categories');

    // Invoice Routes
    Route::resource('business-entities.invoices', InvoiceController::class);
    Route::get('business-entities/{businessEntity}/invoices/{invoice}/post', [InvoiceController::class, 'postRedirect'])->name('business-entities.invoices.post.get');
    Route::post('business-entities/{businessEntity}/invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('business-entities.invoices.post');
    Route::post('business-entities/{businessEntity}/invoices/{invoice}/record-payment', [InvoiceController::class, 'recordPayment'])->name('business-entities.invoices.record-payment');
    Route::post('business-entities/{businessEntity}/invoices/{invoice}/remind', [InvoiceController::class, 'remind'])->name('business-entities.invoices.remind');

    // Tracking Categories Routes
    Route::resource('business-entities.tracking-categories', TrackingCategoryController::class);
    Route::resource('business-entities.tracking-categories.tracking-sub-categories', TrackingSubCategoryController::class)->except(['index', 'show']);

    // Rent Invoice Routes
    Route::get('business-entities/{businessEntity}/rent-invoices', [RentInvoiceController::class, 'index'])->name('business-entities.rent-invoices.index');
    Route::post('business-entities/{businessEntity}/rent-invoices/generate-all', [RentInvoiceController::class, 'generateAll'])->name('business-entities.rent-invoices.generate-all');
    Route::post('business-entities/{businessEntity}/rent-invoices/generate-lease/{lease}', [RentInvoiceController::class, 'generateForLease'])->name('business-entities.rent-invoices.generate-lease');
    Route::get('business-entities/{businessEntity}/rent-invoices/preview/{lease}', [RentInvoiceController::class, 'preview'])->name('business-entities.rent-invoices.preview');
    Route::get('business-entities/{businessEntity}/rent-invoices/suite-assets', [RentInvoiceController::class, 'getSuiteAssets'])->name('business-entities.rent-invoices.suite-assets');
    Route::get('business-entities/{businessEntity}/rent-invoices/upcoming', [RentInvoiceController::class, 'getUpcomingInvoices'])->name('business-entities.rent-invoices.upcoming');

    // Global chart of accounts (single shared GL for all entities)
    Route::resource('chart-of-accounts', ChartOfAccountController::class)->except(['show']);
    Route::resource('vendors', VendorController::class)->except(['show']);
    Route::post('vendors/auto-link-all', [VendorController::class, 'autoLinkAll'])->name('vendors.auto-link-all');
    Route::post('vendors/sync-all-names', [VendorController::class, 'syncAllNames'])->name('vendors.sync-all-names');
    Route::post('vendors/resolve-unlinked', [VendorController::class, 'resolveUnlinked'])->name('vendors.resolve-unlinked');
    Route::post('vendors/{vendor}/link-transactions', [VendorController::class, 'linkTransactions'])->name('vendors.link-transactions');
    Route::get('/bank-accounts', [BusinessEntityController::class, 'bankAccountsIndex'])->name('bank-accounts.index');
    Route::get('/bank-accounts/workspace', [BankAccountPanelController::class, 'portfolioWorkspace'])->name('bank-accounts.workspace');
    Route::get('/bank-accounts/form/create', [BankAccountPanelController::class, 'portfolioCreateForm'])->name('bank-accounts.form.create');
    Route::get('/bank-accounts/{bankAccount}/form/edit', [BankAccountPanelController::class, 'portfolioEditForm'])->name('bank-accounts.form.edit');
    Route::get('/bank-accounts/create', [BusinessEntityController::class, 'createPortfolioBankAccount'])->name('bank-accounts.create');
    Route::post('/bank-accounts', [BusinessEntityController::class, 'storePortfolioBankAccount'])->name('bank-accounts.store');
    Route::get('/bank-accounts/{bankAccount}/edit', [BusinessEntityController::class, 'editPortfolioBankAccount'])->name('bank-accounts.edit');
    Route::put('/bank-accounts/{bankAccount}', [BusinessEntityController::class, 'updatePortfolioBankAccount'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{bankAccount}', [BusinessEntityController::class, 'destroyPortfolioBankAccount'])->name('bank-accounts.destroy');
    Route::get('/bank-accounts/{bankAccount}/reveal-account-number', [BusinessEntityController::class, 'revealBankAccountNumber'])->name('bank-accounts.reveal-account-number');
    Route::get('/transactions', [BusinessEntityController::class, 'transactionsIndex'])->name('transactions.index');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');
    Route::get('/financial-reports/entity-summary', [FinancialReportController::class, 'entitySummaryHub'])->name('financial-reports.entity-summary');
    Route::get('/financial-reports/profit-loss', [FinancialReportController::class, 'profitLossHub'])->name('financial-reports.profit-loss');
    Route::get('/financial-reports/balance-sheet', [FinancialReportController::class, 'balanceSheetHub'])->name('financial-reports.balance-sheet');
    Route::get('/financial-reports/cash-flow', [FinancialReportController::class, 'cashFlowHub'])->name('financial-reports.cash-flow');
    Route::get('/financial-reports/account-transactions', [FinancialReportController::class, 'accountTransactionsHub'])->name('financial-reports.account-transactions');
    Route::get('/financial-reports/tracking-categories', [FinancialReportController::class, 'trackingCategoriesHub'])->name('financial-reports.tracking-categories');
    Route::get('/financial-reports/commitments', [CommitmentController::class, 'report'])->name('financial-reports.commitments');
    Route::get('/bank-import', [BankImportController::class, 'index'])->name('bank-import.index');

    Route::get('/financial-reports/car-register', [CarReportController::class, 'carRegister'])->name('financial-reports.car-register');
    Route::get('/financial-reports/compliance-gaps', [ComplianceReportController::class, 'missingItr'])->name('financial-reports.compliance-gaps');
    Route::get('/financial-reports/ato-lodgements', [ComplianceReportController::class, 'atoLodgements'])->name('financial-reports.ato-lodgements');
    Route::redirect('/financial-reports/fleet-register', '/financial-reports/car-register');
    Route::get('/financial-reports/asset-summary', [PropertyReportController::class, 'assetSummary'])->name('financial-reports.asset-summary');
    Route::get('/portfolio', [PropertyReportController::class, 'portfolio'])->name('portfolio.index');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/financials', [PropertyReportController::class, 'show'])->name('assets.financials');
});

Route::middleware(['auth', 'super.admin'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/workspace', [AdminUsersWorkspaceController::class, 'workspace'])->name('admin.users.workspace');
    Route::get('/admin/users/form/create', [AdminUsersWorkspaceController::class, 'createForm'])->name('admin.users.form.create');
    Route::get('/admin/users/{user}/form/password', [AdminUsersWorkspaceController::class, 'passwordForm'])->name('admin.users.form.password');
    Route::get('/admin/users/create', [UserManagementController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}/activate', [UserManagementController::class, 'activate'])->name('admin.users.activate');
    Route::patch('/admin/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('admin.users.deactivate');
    Route::patch('/admin/users/{user}/password', [UserManagementController::class, 'updatePassword'])->name('admin.users.password');
    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
});

require __DIR__.'/auth.php';
