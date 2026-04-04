<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetInvoiceController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BankImportController;
use App\Http\Controllers\BillsTasksController;
use App\Http\Controllers\BusinessEntityController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ContactListController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentWorkspaceController;
use App\Http\Controllers\Email\GmailController;
use App\Http\Controllers\Email\MailMessageController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EntityPersonController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RentInvoiceController;
use App\Http\Controllers\TrackingCategoryController;
use App\Http\Controllers\TrackingSubCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
    Route::resource('business-entities', BusinessEntityController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
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

    // Assets (Nested under Business Entities)
    Route::resource('business-entities.assets', AssetController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('business-entities/{businessEntity}/assets/{asset}/finalize/{type}', [AssetController::class, 'finalizeDueDate'])->name('assets.finalize-due-date');
    Route::post('business-entities/{businessEntity}/assets/{asset}/extend/{type}', [AssetController::class, 'extendDueDate'])->name('assets.extend-due-date');

    // Asset Notes Routes
    Route::get('business-entities/{businessEntity}/assets/{asset}/notes/create', [AssetController::class, 'createNote'])->name('business-entities.assets.notes.create');
    Route::post('business-entities/{businessEntity}/assets/{asset}/notes', [AssetController::class, 'storeNote'])->name('business-entities.assets.notes.store');
    Route::delete('business-entities/{businessEntity}/assets/{asset}/notes/{note}', [AssetController::class, 'destroyNote'])->name('business-entities.assets.notes.destroy');

    // Asset Document Routes
    Route::post('/business-entities/{businessEntity}/assets/{asset}/documents', [DocumentController::class, 'uploadAssetDocument'])->name('business-entities.assets.documents.store');
    Route::get('/business-entities/{businessEntity}/documents/{document}/content', [DocumentController::class, 'streamDocument'])
        ->name('business-entities.documents.content');

    // Tenant and Lease Routes
    Route::get('/business-entities/{businessEntity}/assets/{asset}/tenants/create', [AssetController::class, 'createTenant'])->name('business-entities.assets.tenants.create');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/tenants', function ($businessEntity, $asset) {
        return redirect()->route('business-entities.assets.tenants.create', [$businessEntity, $asset]);
    })->name('business-entities.assets.tenants.index');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/tenants', [AssetController::class, 'storeTenant'])->name('business-entities.assets.tenants.store');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/leases/create', [AssetController::class, 'createLease'])->name('business-entities.assets.leases.create');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/leases', [AssetController::class, 'storeLease'])->name('business-entities.assets.leases.store');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/leases/sync-from-tenants', [AssetController::class, 'syncLeasesFromTenants'])->name('business-entities.assets.leases.sync-from-tenants');
    Route::post('business-entities/{businessEntity}/assets/{asset}/invoices/create-for-lease', [AssetInvoiceController::class, 'storeForLease'])->name('assets.invoices.store-for-lease');

    // Entity Persons
    Route::get('entity-persons/create/{business_entity_id}', [EntityPersonController::class, 'create'])->name('entity-persons.create');
    Route::resource('entity-persons', EntityPersonController::class)->except(['create', 'destroy']);
    Route::post('entity-persons/{entityPerson}/finalize-due-date', [EntityPersonController::class, 'finalizeDueDate'])->name('entity-persons.finalize-due-date');
    Route::post('entity-persons/{entityPerson}/extend-due-date', [EntityPersonController::class, 'extendDueDate'])->name('entity-persons.extend-due-date');

    // Person routes
    Route::get('persons', [EntityPersonController::class, 'indexPersons'])->name('persons.index');
    Route::get('persons/create', [EntityPersonController::class, 'createPerson'])->name('persons.create');
    Route::post('persons', [EntityPersonController::class, 'storePerson'])->name('persons.store');
    Route::get('persons/{person}', [EntityPersonController::class, 'showPerson'])->name('persons.show');

    // Bank Accounts
    Route::get('/business-entities/{businessEntity}/bank-accounts/create', [BusinessEntityController::class, 'createBankAccount'])->name('business-entities.bank-accounts.create');
    Route::get('/business-entities/{businessEntity}/bank-accounts', function ($businessEntity) {
        return redirect()->route('business-entities.show', $businessEntity);
    });
    Route::post('/business-entities/{businessEntity}/bank-accounts', [BusinessEntityController::class, 'storeBankAccount'])->name('business-entities.bank-accounts.store');
    Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/edit', [BusinessEntityController::class, 'editBankAccount'])->name('business-entities.bank-accounts.edit');
    Route::put('/business-entities/{businessEntity}/bank-accounts/{bankAccount}', [BusinessEntityController::class, 'updateBankAccount'])->name('business-entities.bank-accounts.update');

    // Transaction Routes
    Route::post('business-entities/{businessEntity}/transactions/store', [BusinessEntityController::class, 'storeTransaction'])->name('business-entities.transactions.store');
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

    // Chart of Accounts API
    Route::get('/business-entities/{businessEntity}/chart-of-accounts', [ChartOfAccountController::class, 'getAccountsJson'])->name('business-entities.chart-of-accounts.api');

    // API for Asset Documents
    Route::get('/api/business-entities/{businessEntity}/assets/{asset}/documents', [DocumentController::class, 'fetchAssetFiles'])->name('api.asset-documents.fetch');
    Route::get('/api/business-entities/{businessEntity}/assets/{asset}/documents/{document}/preview', [DocumentController::class, 'previewDocument'])->name('api.asset-documents.preview');

    // Document Management Routes
    Route::controller(DocumentController::class)->group(function () {
        Route::post('/documents/fetch-files', 'fetchFiles')->name('documents.fetchFiles');
        Route::post('/documents/get-link', 'getFileLink')->name('documents.getLink');
        Route::post('/documents/delete', [DocumentController::class, 'deleteFile'])->name('documents.delete');

        Route::post('/business-entities/{businessEntity}/documents/fetch', 'fetchFiles')->name('business-entities.documents.fetch');

        Route::post('/business-entities/{businessEntity}/assets/{asset}/documents/fetch', 'fetchAssetFiles')->name('asset-documents.fetchAssetFiles');
        Route::post('/business-entities/{businessEntity}/assets/{asset}/documents/delete', [DocumentController::class, 'deleteFile'])->name('asset-documents.delete');
    });

    // Reminder routes
    Route::resource('reminders', ReminderController::class);
    Route::post('reminders/{reminder}/complete', [ReminderController::class, 'complete'])->name('reminders.complete');
    Route::post('reminders/{reminder}/extend', [ReminderController::class, 'extend'])->name('reminders.extend');
    Route::post('reminders/bulk-complete', [ReminderController::class, 'bulkComplete'])->name('reminders.bulk-complete');

    // Contact List Routes
    Route::resource('business-entities.contact-lists', ContactListController::class);

    // Email Section
    Route::get('/emails', [MailMessageController::class, 'index'])->name('emails.index');
    Route::get('/emails/upload', [MailMessageController::class, 'uploadIndex'])->name('emails.upload');
    Route::post('/emails/upload', [MailMessageController::class, 'uploadMsg'])->name('emails.upload.store');
    Route::get('/emails/{id}', [MailMessageController::class, 'show'])->name('emails.show');
    Route::get('/emails/{id}/reply', [MailMessageController::class, 'reply'])->name('emails.reply');
    Route::get('/emails/{id}/reply-data', [MailMessageController::class, 'getReplyData'])->name('emails.reply-data');
    Route::post('/emails/{id}/allocate-entity', [MailMessageController::class, 'allocateToBusinessEntity'])->name('emails.allocate.entity');
    Route::post('/emails/{id}/allocate-asset', [MailMessageController::class, 'allocateToAsset'])->name('emails.allocate.asset');
    Route::get('/emails-sync', [GmailController::class, 'sync'])->name('emails.sync');

    Route::post('/emails/send', [MailMessageController::class, 'sendEmail'])->name('emails.send');
    Route::post('/emails/save-draft', [MailMessageController::class, 'saveDraft'])->name('emails.save-draft');
    Route::get('/emails/drafts', [MailMessageController::class, 'drafts'])->name('emails.drafts');

    // Email Template Management Routes
    Route::resource('email-templates', EmailTemplateController::class);
    Route::get('/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::get('/email-templates-api/templates', [EmailTemplateController::class, 'getTemplates'])->name('email-templates.api');

    // Email Routes (Nested under Business Entities)
    Route::get('business-entities/{businessEntity}/compose-email-data', [BusinessEntityController::class, 'getComposeEmailData'])->name('business-entities.compose-email-data');
    Route::post('business-entities/{businessEntity}/send-email', [BusinessEntityController::class, 'sendEmail'])->name('business-entities.send-email');

    // Accounting System Routes
    Route::resource('business-entities.chart-of-accounts', ChartOfAccountController::class);
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

    // Global Accounting Routes
    Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('chart-of-accounts.index');
    Route::get('/bank-accounts', [BusinessEntityController::class, 'bankAccountsIndex'])->name('bank-accounts.index');
    Route::get('/transactions', [BusinessEntityController::class, 'transactionsIndex'])->name('transactions.index');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');
    Route::get('/bank-import', [BankImportController::class, 'index'])->name('bank-import.index');
});

Route::middleware(['auth', 'super.admin'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [UserManagementController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}/activate', [UserManagementController::class, 'activate'])->name('admin.users.activate');
    Route::patch('/admin/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('admin.users.deactivate');
    Route::patch('/admin/users/{user}/password', [UserManagementController::class, 'updatePassword'])->name('admin.users.password');
    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
});

require __DIR__.'/auth.php';
