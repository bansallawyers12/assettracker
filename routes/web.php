<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BusinessEntityController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\EntityPersonController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\ReminderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Controllers\ContactListController;
use App\Http\Controllers\Email\MailMessageController;
use App\Http\Controllers\Email\GmailController;
use App\Http\Controllers\Email\EmailUploadController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\FinancialReportController;


Route::post('/test-json', function() {
    return response()->json(['status' => 'success', 'message' => 'This is a test JSON response']);
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', EnsureTwoFactorVerified::class])->group(function () {
    Route::get('/dashboard', [BusinessEntityController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', EnsureTwoFactorVerified::class])->group(function () {
    // Business Entities
    Route::resource('business-entities', BusinessEntityController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('business-entities/{businessEntity}/notes', [BusinessEntityController::class, 'storeNote'])->name('business-entities.notes.store');
    Route::delete('business-entities/{businessEntity}/notes/{note}', [BusinessEntityController::class, 'destroyNote'])->name('business-entities.notes.destroy');
    Route::post('business-entities/{businessEntity}/import-persons', [BusinessEntityController::class, 'importPersons'])->name('business-entities.import-persons');
    Route::post('business-entities/{businessEntity}/upload-document', [DocumentController::class, 'uploadDocument'])->name('business-entities.upload-document');
    Route::post('business-entities/{businessEntity}/transactions/{transaction}/match', [BusinessEntityController::class, 'matchTransaction'])->name('business-entities.transactions.match');

    // Contact List Routes (Nested under Business Entities)
    // Notes
    Route::post('notes/{note}/finalize', [AssetController::class, 'finalizeNote'])->name('notes.finalize');
    Route::post('notes/{note}/extend', [AssetController::class, 'extendNote'])->name('notes.extend');

    // Assets (Nested under Business Entities)
    Route::resource('business-entities.assets', AssetController::class)->only(['create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('business-entities/{businessEntity}/assets/{asset}/finalize/{type}', [AssetController::class, 'finalizeDueDate'])->name('assets.finalize-due-date');
    Route::post('business-entities/{businessEntity}/assets/{asset}/extend/{type}', [AssetController::class, 'extendDueDate'])->name('assets.extend-due-date');
    
    // Asset Notes Routes
    Route::get('business-entities/{businessEntity}/assets/{asset}/notes/create', [AssetController::class, 'createNote'])->name('business-entities.assets.notes.create');
    Route::post('business-entities/{businessEntity}/assets/{asset}/notes', [AssetController::class, 'storeNote'])->name('business-entities.assets.notes.store');
    Route::delete('business-entities/{businessEntity}/assets/{asset}/notes/{note}', [AssetController::class, 'destroyNote'])->name('business-entities.assets.notes.destroy');
    
    // Asset Document Routes
    Route::post('/business-entities/{businessEntity}/assets/{asset}/documents', [App\Http\Controllers\DocumentController::class, 'uploadAssetDocument'])->name('business-entities.assets.documents.store');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/documents/fetch', [DocumentController::class, 'fetchAssetFiles'])->name('asset-documents.fetchAssetFiles');
    
    // Tenant and Lease Routes
    Route::get('/business-entities/{businessEntity}/assets/{asset}/tenants/create', [AssetController::class, 'createTenant'])->name('business-entities.assets.tenants.create');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/tenants', [AssetController::class, 'storeTenant'])->name('business-entities.assets.tenants.store');
    Route::get('/business-entities/{businessEntity}/assets/{asset}/leases/create', [AssetController::class, 'createLease'])->name('business-entities.assets.leases.create');
    Route::post('/business-entities/{businessEntity}/assets/{asset}/leases', [AssetController::class, 'storeLease'])->name('business-entities.assets.leases.store');

    // Entity Persons
    Route::get('entity-persons/create/{business_entity_id}', [EntityPersonController::class, 'create'])->name('entity-persons.create');
    Route::resource('entity-persons', EntityPersonController::class)->except(['create', 'destroy']);
    Route::post('entity-persons/{entityPerson}/finalize-due-date', [EntityPersonController::class, 'finalizeDueDate'])->name('entity-persons.finalize-due-date');
    Route::post('entity-persons/{entityPerson}/extend-due-date', [EntityPersonController::class, 'extendDueDate'])->name('entity-persons.extend-due-date');

    // Person details route - show all roles for a person across all entities
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
    Route::post('business-entities/{businessEntity}/transactions/{transaction}/match', [BusinessEntityController::class, 'matchTransaction'])->name('business-entities.transactions.match');

    // Existing Bank Account Transaction Routes
    Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions/create', [BusinessEntityController::class, 'createTransaction'])->name('business-entities.bank-accounts.transactions.create');
    Route::post('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions', [BusinessEntityController::class, 'storeTransaction'])->name('business-entities.bank-accounts.transactions.store');
    Route::get('/business-entities/{businessEntity}/bank-accounts/{bankAccount}/transactions/{transaction}', [BusinessEntityController::class, 'showTransaction'])->name('business-entities.bank-accounts.transactions.show');
    Route::post('/business-entities/{businessEntity}/bank-accounts/{bankStatementEntry}/match-transaction', [BusinessEntityController::class, 'matchTransaction'])->name('business-entities.bank-accounts.match-transaction');

    // API for Bank Accounts
    Route::get('/api/business-entities/{businessEntity}/bank-accounts', [BusinessEntityController::class, 'getBankAccounts'])->name('business-entities.bank-accounts.api');

    // API for Asset Documents
    Route::get('/api/business-entities/{businessEntity}/assets/{asset}/documents', [DocumentController::class, 'fetchAssetFiles'])->name('api.asset-documents.fetch');
    Route::get('/api/business-entities/{businessEntity}/assets/{asset}/documents/{document}/preview', [DocumentController::class, 'previewDocument'])->name('api.asset-documents.preview');

    // Document Management Routes - Now using DocumentController
    Route::controller(DocumentController::class)->group(function () {
        // General document routes
        Route::post('/documents/fetch-files', 'fetchFiles')->name('documents.fetchFiles');
        Route::post('/documents/get-link', 'getFileLink')->name('documents.getLink');
        Route::post('/documents/upload', 'upload')->name('documents.upload');
        Route::post('/documents/delete', [DocumentController::class, 'deleteFile'])->name('documents.delete');
        Route::get('/documents', 'index')->name('documents.index');
        Route::get('/documents/proxy', 'proxyFile')->name('documents.proxy');

        // Business Entity document routes
        Route::post('/business-entities/{businessEntity}/documents/fetch', 'fetchFiles')->name('business-entities.documents.fetch');
        Route::post('/business-entities/{businessEntity}/documents/upload', 'uploadDocument')->name('business-entities.upload-document');

        // Asset-specific document routes
        Route::post('/business-entities/{businessEntity}/assets/{asset}/documents/fetch', 'fetchAssetFiles')->name('asset-documents.fetchAssetFiles');
        Route::post('/business-entities/{businessEntity}/assets/{asset}/documents/delete', [DocumentController::class, 'deleteFile'])->name('asset-documents.delete');
        Route::get('/asset-documents/preview/{path}', 'previewDocument')
            ->name('asset-documents.previewAssetDocument')
            ->where('path', '.*');
    });

    // Reminder routes
    Route::resource('reminders', ReminderController::class);
    Route::post('reminders/{reminder}/complete', [ReminderController::class, 'complete'])->name('reminders.complete');
    Route::post('reminders/{reminder}/extend', [ReminderController::class, 'extend'])->name('reminders.extend');
    Route::post('reminders/bulk-complete', [ReminderController::class, 'bulkComplete'])->name('reminders.bulk-complete');

    // Contact List Routes (Nested under Business Entities)
    Route::resource('business-entities.contact-lists', ContactListController::class);

    // Email Section
    Route::get('/emails', [MailMessageController::class, 'index'])->name('emails.index');
    Route::get('/emails/{id}', [MailMessageController::class, 'show'])->name('emails.show');
    Route::get('/emails/{id}/reply', [MailMessageController::class, 'reply'])->name('emails.reply');
    Route::get('/emails/{id}/reply-data', [MailMessageController::class, 'getReplyData'])->name('emails.reply-data');
    Route::post('/emails/{id}/allocate-entity', [MailMessageController::class, 'allocateToBusinessEntity'])->name('emails.allocate.entity');
    Route::post('/emails/{id}/allocate-asset', [MailMessageController::class, 'allocateToAsset'])->name('emails.allocate.asset');
    Route::get('/emails-sync', [GmailController::class, 'sync'])->name('emails.sync');
    
    // New Email Compose Routes
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

    // Accounting System Routes (Nested under Business Entities)
    Route::resource('business-entities.chart-of-accounts', ChartOfAccountController::class);
    Route::get('business-entities/{businessEntity}/financial-reports/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('business-entities.financial-reports.profit-loss');
    Route::get('business-entities/{businessEntity}/financial-reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('business-entities.financial-reports.balance-sheet');
    Route::get('business-entities/{businessEntity}/financial-reports/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('business-entities.financial-reports.cash-flow');
});

Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/two-factor-challenge', function () {
    return view('auth.two-factor-challenge');
})->name('two-factor.challenge');

Route::post('/two-factor-challenge', [AuthenticatedSessionController::class, 'verifyTwoFactor'])
    ->name('two-factor.login');

// Add this route for testing email
Route::get('/test-email', function () {
    try {
        $user = \App\Models\User::first();
        if ($user) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->notify(new \App\Notifications\TwoFactorCode($code));
            return 'Test email sent successfully to: ' . $user->email;
        }
        return 'No user found to send test email';
    } catch (\Exception $e) {
        return 'Error sending email: ' . $e->getMessage();
    }
});

require __DIR__.'/auth.php';