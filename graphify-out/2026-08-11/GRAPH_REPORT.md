# Graph Report - assettracker  (2026-08-11)

## Corpus Check
- 687 files · ~275,573 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 3877 nodes · 8476 edges · 534 communities (432 shown, 102 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 497 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `cc9b918f`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- BusinessEntity
- EncryptsAttributes.php
- BankAccountFormTest
- Illuminate\Http\Resources\Json\JsonResource
- ComplianceCategory
- Encrypted Backup Service
- workspace-panel.js
- Illuminate\Database\Seeder
- Reminder
- Illuminate\Http\RedirectResponse
- DocumentStorage
- showWorkspaceAlert
- Illuminate\Http\JsonResponse
- Invoice
- app.js
- Frontend Dependencies
- tomselect-init.js
- EnsuresOperationalBusinessEntity.php
- Asset
- AssetController
- EncryptionHelper
- AppServiceProvider.php
- bank-account-modal.js
- EntityPerson
- ComplianceYearRecord
- Carbon\Carbon
- Transaction
- Vendor
- User
- FinancialReportService
- BankAccountMaskingTest
- Lease
- self
- Project Documentation
- ComplianceYearServiceTest
- Commitment
- BankAccountStatement
- ComplianceReportController
- BusinessEntityController
- MailMessage
- BusinessEntityController.php
- Illuminate\Database\Migrations\Migration
- ASIC Renewal Tests
- Bank PDF Parsing Logic
- Asset Tracker
- compliance-workspace.js
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Transaction Payment UI
- TableSortTest
- .rule
- TransactionPostingService
- Transaction.php
- Rich Text Editor Config
- MailMessageController
- ReportEntityScopeLabel
- User.php
- BankAccountAssetLinkService
- Illuminate\Support\Collection
- UserManagementController
- DocumentUploadValidation
- PropertyReportService
- scripts
- Illuminate\Console\Command
- BankStatementEntry
- BankAccount
- Pest Testing Documentation
- TwoFactorService
- ComplianceReminderService
- ChecklistFilenameMatcher
- BusinessEntityRegistrationDateTest
- Tailwind CSS Documentation
- Illuminate\Database\Eloquent\Model
- Address Formatting Utility
- Closure
- Entity Blade Partials
- PDF Text Extraction
- Form Submission UI
- ChartOfAccount
- JournalEntry
- Compliance Feature Roadmap
- Document
- Illuminate\Database\Eloquent\Relations\HasMany
- composer.json
- Production Dependencies
- FinancialYear
- ATO Filing Reference
- Development Dependencies
- TestCase
- main
- CommitmentReportService
- DocumentCategory
- Tech Update Plans
- Laravel Framework Keywords
- Bank Statement Row Parsing
- Reminder Authorization Policy
- ReportEntityScopeResolver
- Chart of Accounts Documentation
- Illuminate\Http\Request
- Code Review Checklist
- Convention Inference Guide
- .update
- Security Audit Service
- Tenant
- SetupSecurity
- Lodgement Reporting Logic
- Asset Workspace Partials
- Composer Plugin Configuration
- Compliance Category Migrations
- Architecture Best Practices
- Compliance Project Context
- Encrypted Backup Restoration
- Laravel Configuration Standards
- Email Parsing API
- Asset Form JavaScript
- Encrypted User Provider
- PSR-4 Autoload Mapping
- Person Detail Views
- User Profile Views
- Event Service Provider
- Route Service Provider
- Layout Navigation Components
- Entity Encryption Migration
- Account Purpose Migration
- Email Hash Index Migration
- Mail Foreign Key Migration
- Asset Creation Partials
- Asset Editing Partials
- Transaction Creation Partials
- Transaction Editing Partials
- HTTP Kernel Middleware
- AssetSummaryReportService
- Bank Account UI Components
- Security Best Practices
- EmailTemplate
- BankImportController
- Illuminate\View\View
- PHPUnit\Framework\TestCase
- Queue Best Practices
- Console Kernel Scheduling
- extra
- Bug Tracking Notes
- Frontend Refactoring Plan
- UX Helper Plan
- Deployment Documentation
- Icon Management Plan
- Dashboard Transaction UI
- Global Search Navigation
- Person List and Stats
- Bank Account Creation UI
- Bank Account Edit UI
- Entity Edit Form
- Portfolio Creation Form
- Portfolio Edit Form
- Business Bank Account Creation
- Business Bank Account Edit
- Business Bank Account Form
- Person Bank Account Form
- Bank Account Forms
- Vendor Management Forms
- User List View
- User Row Actions
- Lease Edit Form
- Tenant Edit Form
- Bank Account Picker
- Banking Detail Cells
- Bank Account Portfolio List
- Business Entity Assets
- Contact List Workspace
- Business Entity Notes
- Business Entity Persons
- Email Template List
- Email Template Actions
- Transaction Line Details
- Financial Report Filters
- Portfolio Report Filters
- Financial Report Scope
- Python Dependency Script
- Email Service Script
- Application Startup Script
- Asset Due Dates Card
- Asset Index View
- Asset Sidebar Details
- Bank Account Link Actions
- Loan Banking Form
- Bank Account Configuration Modal
- Grouped Bank Account List
- Business Entity Creation
- Rent Collection Fields
- Entity Bank Account List
- Rent Asset Form
- Person Detail Actions
- Person List Actions
- Profile Creation Fields
- Commitment Creation
- Commitment Editing
- ATO Compliance Warnings
- Missing ITR Reports
- Person Bank Account List
- Person Bank Account Modal
- Vendor Creation Form
- System Feature Areas
- Advanced Query Best Practices
- Invoices and Rent Area
- Commitments and Tracking Area
- Financial Reporting Area
- Email and Templates Area
- Reminders and Contacts Area
- API and Policies Area
- Auth and Security Area
- Admin Management Area
- Dashboard and Profile Area
- Business Entities Area
- Persons and Roles Area
- Asset Lifecycle Area
- Documents Area
- Compliance Risk Levels
- Banking Transaction Categories
- MCP Configuration
- ComplianceDocumentFile
- BankStatementPdfParseService
- Database Performance Best Practices
- Events and Notifications Best Practices
- Rent Collection Link Form
- BusinessEntity.php
- Bank Statement List
- Caching Best Practices
- Eloquent Best Practices
- Migration Best Practices
- Currency Parsing Utilities
- System Architecture Overview
- Import Reconciliation Panel
- Bank Reconciliation UI
- Statement Date Parsing
- .canAccessEntity
- CarReportService
- EncryptedFilesystemAdapter
- Blade View Best Practices
- Error Handling Best Practices
- Task Scheduling Best Practices
- Testing Best Practices
- Collection Best Practices
- HTTP Client Best Practices
- Mail Best Practices
- Routing Best Practices
- Code Style Conventions
- Validation Best Practices
- AssetsWorkspaceController
- .handle
- PDF Parser Integration
- Transaction Text Grouping
- Email Message Parser
- .handle

## God Nodes (most connected - your core abstractions)
1. `BusinessEntity` - 477 edges
2. `BankAccount` - 185 edges
3. `Asset` - 154 edges
4. `Transaction` - 114 edges
5. `BusinessEntityController` - 111 edges
6. `User` - 98 edges
7. `Controller` - 71 edges
8. `ChartOfAccount` - 64 edges
9. `FinancialReportService` - 63 edges
10. `ComplianceDocumentFile` - 53 edges

## Surprising Connections (you probably didn't know these)
- `up()` --calls--> `Asset`  [INFERRED]
  database/migrations/2026_03_29_100001_migrate_legacy_transaction_receipts_to_documents.php → app/Models/Asset.php
- `up()` --calls--> `Asset`  [INFERRED]
  database/migrations/2026_03_29_100002_backfill_imported_email_document_categories.php → app/Models/Asset.php
- `down()` --calls--> `BankAccount`  [INFERRED]
  database/migrations/2026_06_15_100003_migrate_rent_banking_to_bank_accounts.php → app/Models/BankAccount.php
- `up()` --calls--> `BankAccount`  [INFERRED]
  database/migrations/2026_06_15_100003_migrate_rent_banking_to_bank_accounts.php → app/Models/BankAccount.php
- `up()` --calls--> `BusinessEntity`  [INFERRED]
  database/migrations/2026_03_22_000001_create_real_estate_companies_and_migrate_tenant_links.php → app/Models/BusinessEntity.php

## Import Cycles
- None detected.

## Communities (534 total, 102 thin omitted)

### Community 0 - "BusinessEntity"
Cohesion: 0.04
Nodes (6): ContactListController, ContactListsWorkspaceController, FinancialReportController, BusinessEntity, ContactList, ContactListPolicy

### Community 1 - "EncryptsAttributes.php"
Cohesion: 0.38
Nodes (11): addEncryptedAttribute(), attributesToArray(), decrypt(), decryptAttributes(), encrypt(), encryptAttributes(), getAttribute(), getEncryptedAttributes() (+3 more)

### Community 3 - "Illuminate\Http\Resources\Json\JsonResource"
Cohesion: 0.13
Nodes (9): AssetResource, ComplianceCategoryResource, ComplianceDocumentFileResource, ComplianceDocumentTypeResource, ComplianceYearWorkspaceResource, ContactListResource, DocumentCategoryResource, DocumentSlotResource (+1 more)

### Community 6 - "workspace-panel.js"
Cohesion: 0.14
Nodes (48): alertHttpError(), boot(), initAdminUsersWorkspace(), pageQuery(), withPageQuery(), workspaceUrl(), boot(), ensurePanelFormHandlers() (+40 more)

### Community 7 - "Illuminate\Database\Seeder"
Cohesion: 0.36
Nodes (3): ChartOfAccountSeeder, DatabaseSeeder, Illuminate\Database\Seeder

### Community 8 - "Reminder"
Cohesion: 0.08
Nodes (5): BillsTasksController, Collection, ReminderController, Reminder, Illuminate\Database\Eloquent\Builder

### Community 9 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.07
Nodes (18): AuthenticatedSessionController, ConfirmablePasswordController, EmailVerificationNotificationController, EmailVerificationPromptController, NewPasswordController, PasswordController, PasswordResetLinkController, TwoFactorController (+10 more)

### Community 10 - "DocumentStorage"
Cohesion: 0.12
Nodes (4): DocumentStorage, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 11 - "showWorkspaceAlert"
Cohesion: 0.11
Nodes (34): alertHttpError(), alertHttpError(), alertValidationErrors(), api(), boot(), buildRowActionsHtml(), buildSlotRow(), capitalize() (+26 more)

### Community 12 - "Illuminate\Http\JsonResponse"
Cohesion: 0.07
Nodes (8): BankAccountPanelController, BankAccountsWorkspaceController, EntityShowWorkspaceController, PersonShowWorkspaceController, PersonsWorkspaceController, EntityPersonResource, Person, Illuminate\Http\JsonResponse

### Community 13 - "Invoice"
Cohesion: 0.07
Nodes (17): GmailSyncCommand, InvoiceController, SyncGmailForUser, ContactEmail, InvoiceReminderMail, Invoice, GmailFetcher, InvoicePaymentService (+9 more)

### Community 14 - "app.js"
Cohesion: 0.09
Nodes (38): initAddressFieldSync(), syncAddressFieldsInForm(), bootApp(), exposeRichTextHelpers(), loadRichTextModule(), initFormPlugins(), initEntityCreateForm(), initEntityFormFields() (+30 more)

### Community 15 - "Frontend Dependencies"
Cohesion: 0.05
Nodes (39): alpinejs, concurrently, flatpickr, laravel-vite-plugin, dependencies, flatpickr, @tiptap/core, @tiptap/extension-color (+31 more)

### Community 16 - "tomselect-init.js"
Cohesion: 0.12
Nodes (37): tom-select, initPersonForm(), initPersonsToggleLogic(), isSelectInVisibleSection(), refreshPersonFormSelects(), scheduleRefreshPersonFormSelects(), activateTomSelectsIn(), addNativeOptionToTomSelect() (+29 more)

### Community 17 - "EnsuresOperationalBusinessEntity.php"
Cohesion: 0.09
Nodes (7): ensureNotClosed(), ensureOperationalForAccounting(), ManualJournalEntryController, TrackingCategoryController, TrackingSubCategoryController, TrackingCategory, TrackingSubCategory

### Community 18 - "Asset"
Cohesion: 0.09
Nodes (4): AssetShowWorkspaceController, Asset, down(), up()

### Community 20 - "EncryptionHelper"
Cohesion: 0.18
Nodes (3): BackfillPersonsEncryption, ReencryptModelAttributes, EncryptionHelper

### Community 21 - "AppServiceProvider.php"
Cohesion: 0.16
Nodes (6): AppServiceProvider, EncryptedFilesystemServiceProvider, HeaderSearchIndex, PasswordPolicy, Illuminate\Support\ServiceProvider, Illuminate\Validation\Rules\Password

### Community 23 - "bank-account-modal.js"
Cohesion: 0.12
Nodes (22): availablePurposes(), buildCreateFormUrl(), getSelectedAccountOption(), getSelectValue(), initBankAccountModal(), parseConfig(), purposesOnEntity(), bindBankFormFields() (+14 more)

### Community 24 - "EntityPerson"
Cohesion: 0.09
Nodes (3): EntityPersonController, EntityPerson, TransactionPayerResolver

### Community 25 - "ComplianceYearRecord"
Cohesion: 0.18
Nodes (3): ComplianceYearRecord, ComplianceYearService, Carbon

### Community 26 - "Carbon\Carbon"
Cohesion: 0.19
Nodes (5): Carbon, AtoDueDateService, Carbon, Carbon\Carbon, Carbon\CarbonInterface

### Community 28 - "Vendor"
Cohesion: 0.15
Nodes (3): VendorController, Vendor, VendorSyncService

### Community 29 - "User"
Cohesion: 0.06
Nodes (7): User, AssetPolicy, BusinessEntityPolicy, CommitmentPolicy, ComplianceDocumentFilePolicy, ComplianceYearRecordPolicy, Illuminate\Foundation\Auth\User

### Community 30 - "FinancialReportService"
Cohesion: 0.09
Nodes (3): FinancialReportService, Carbon, Illuminate\Database\Eloquent\Collection

### Community 32 - "Lease"
Cohesion: 0.13
Nodes (6): AssetInvoiceController, RentInvoiceController, InvoiceLine, Lease, Carbon, RentInvoiceService

### Community 33 - "self"
Cohesion: 0.09
Nodes (3): Carbon, Exists, self

### Community 34 - "Project Documentation"
Cohesion: 0.07
Nodes (27): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+19 more)

### Community 36 - "Commitment"
Cohesion: 0.20
Nodes (3): CommitmentController, Commitment, CommitmentPayment

### Community 37 - "BankAccountStatement"
Cohesion: 0.10
Nodes (5): BankAccountStatementController, BankAccountStatement, BankAccountStatementUploadService, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Http\UploadedFile

### Community 38 - "ComplianceReportController"
Cohesion: 0.33
Nodes (3): ComplianceReportController, Carbon, Symfony\Component\HttpFoundation\StreamedResponse

### Community 40 - "MailMessage"
Cohesion: 0.17
Nodes (5): MailAttachment, MailMessage, MsgParserService, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Relations\BelongsToMany

### Community 41 - "BusinessEntityController.php"
Cohesion: 0.13
Nodes (5): UniqueAbnHash, UniqueAcnHash, UniqueChecklistLabelInCategory, UniqueComplianceLabelInCategory, Illuminate\Contracts\Validation\ValidationRule

### Community 44 - "Bank PDF Parsing Logic"
Cohesion: 0.20
Nodes (21): ensure_westpac_row_width(), is_header_row(), is_westpac_continuation_row(), look_like_money(), merge_continuation_row(), merge_westpac_continuation(), merge_westpac_table_rows(), money_values_from_cells() (+13 more)

### Community 45 - "Asset Tracker"
Cohesion: 0.10
Nodes (20): Accounting, API, Asset Tracker, Bank statements, Communication, Core, Creating users, Database (key tables) (+12 more)

### Community 46 - "compliance-workspace.js"
Cohesion: 0.20
Nodes (19): alertHttpError(), alertValidationErrors(), api(), bindTabActivation(), bootWorkspaces(), buildCategoryPanel(), buildFileRow(), buildStatusCell() (+11 more)

### Community 47 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.07
Nodes (3): EmailDraft, TransactionLine, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 48 - "Transaction Payment UI"
Cohesion: 0.17
Nodes (19): bindBookingEntityChange(), bindPaidBySelectChange(), bindPaidByTomSelectChange(), bindPaymentStatusChange(), bookingEntityId(), initTransactionPaidByBankAccount(), parseEntityIdFromPaidBy(), refreshTransactionPaidByBankAccount() (+11 more)

### Community 51 - "TransactionPostingService"
Cohesion: 0.16
Nodes (3): RepostPaidTransactionJournals, DateTimeInterface, TransactionPostingService

### Community 52 - "Transaction.php"
Cohesion: 0.11
Nodes (4): TransactionCashParts, up(), up(), SplitRemittanceTransactionTest

### Community 53 - "Rich Text Editor Config"
Cohesion: 0.26
Nodes (16): buildToolbar(), cleanupEditorShell(), createDivider(), createEditorShell(), createToolbarButton(), destroyRichTextEditor(), escapeSelectorId(), fieldValue() (+8 more)

### Community 54 - "MailMessageController"
Cohesion: 0.20
Nodes (3): MailMessageController, Closure, MailLabel

### Community 56 - "User.php"
Cohesion: 0.18
Nodes (4): LoginRequest, ProfileUpdateRequest, Illuminate\Foundation\Http\FormRequest, Illuminate\Notifications\Notifiable

### Community 58 - "Illuminate\Support\Collection"
Cohesion: 0.13
Nodes (3): ComplianceReportService, Carbon, Illuminate\Support\Collection

### Community 61 - "PropertyReportService"
Cohesion: 0.16
Nodes (3): Carbon, PropertyReportService, PropertyReportServiceTest

### Community 62 - "scripts"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 63 - "Illuminate\Console\Command"
Cohesion: 0.17
Nodes (6): AddXeroChartOfAccounts, BackfillComplianceDueDates, EnsureComplianceYears, ScheduleBackup, Illuminate\Console\Command, Illuminate\Support\Facades\Schedule

### Community 64 - "BankStatementEntry"
Cohesion: 0.07
Nodes (10): BankAccountImportController, BankAccountTransactionController, BankStatementEntry, BankCsvStatementParser, BankStatementApplyService, BankStatementMatchSuggester, Carbon, BankStatementParseService (+2 more)

### Community 65 - "BankAccount"
Cohesion: 0.05
Nodes (3): BankAccount, BusinessEntityBankAccount, SecurityAuditLogger

### Community 66 - "Pest Testing Documentation"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 71 - "Tailwind CSS Documentation"
Cohesion: 0.14
Nodes (13): Basic Usage, Common Patterns, Common Pitfalls, CSS-First Configuration, Dark Mode, Documentation, Flexbox Layout, Grid Layout (+5 more)

### Community 72 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.13
Nodes (5): DepreciationSchedule, Email, AuthServiceProvider, Illuminate\Database\Eloquent\Model, Illuminate\Foundation\Support\Providers\AuthServiceProvider

### Community 74 - "Closure"
Cohesion: 0.23
Nodes (7): EnsureAccountActive, EnsureSuperAdmin, Response, EnsureTwoFactorEnrolled, SecurityHeaders, Closure, Symfony\Component\HttpFoundation\Response

### Community 75 - "Entity Blade Partials"
Cohesion: 0.15
Nodes (12): business-entities.partials.assets-workspace, business-entities.partials.bank-accounts.list, business-entities.partials.bank-import-summary, business-entities.partials.close-entity-modal, business-entities.partials.contact-lists-workspace, business-entities.partials.entity-details-sidebar, business-entities.partials.notes-workspace, business-entities.partials.persons-workspace (+4 more)

### Community 76 - "PDF Text Extraction"
Cohesion: 0.11
Nodes (9): cells_from_text_line(), disambiguate_amount_balance(), extract_leading_date_from_line(), glue_money_suffix(), infer_sign_from_description(), Attach detached CR/DR markers to their amount: '607.06 Dr' -> '607.06Dr'., Compact PDF text often collapses blank debit/credit cells into: Date |…, Westpac text often keeps date+desc in one cell when amounts use wide gaps. (+1 more)

### Community 77 - "Form Submission UI"
Cohesion: 0.30
Nodes (11): disableSubmitter(), ensureOverlay(), hideFormSaving(), initGlobalFormSaving(), isFormSaving(), isWorkspaceFormSaving(), lockFormFields(), restoreSubmitter() (+3 more)

### Community 79 - "JournalEntry"
Cohesion: 0.09
Nodes (6): PostMonthlyDepreciation, JournalEntry, JournalLine, DepreciationPostingService, DateTimeInterface, ManualJournalEntryService

### Community 80 - "Compliance Feature Roadmap"
Cohesion: 0.14
Nodes (14): ATO Lodgement Tracking — Findings & Proposal, Current compliance config (`config/compliance.php`), Disclaimer, Executive Summary, Part 3 — Gaps, Part 5 — Implementation Plan, Part 6 — Configuration Reference, Part 7 — Recommendation (+6 more)

### Community 81 - "Document"
Cohesion: 0.15
Nodes (3): DocumentController, Document, DocumentUploadService

### Community 82 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.09
Nodes (6): ComplianceDocumentType, down(), up(), up(), ComplianceDocumentTypeSeeder, Illuminate\Database\Eloquent\Relations\HasMany

### Community 83 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 84 - "Production Dependencies"
Cohesion: 0.18
Nodes (11): require, bacon/bacon-qr-code, fakerphp/faker, laravel/framework, laravel/tinker, league/flysystem, league/flysystem-aws-s3-v3, php (+3 more)

### Community 86 - "FinancialYear"
Cohesion: 0.09
Nodes (3): FinancialYear, AtoDueDateServiceTest, FinancialYearTest

### Community 87 - "ATO Filing Reference"
Cohesion: 0.12
Nodes (17): Annual GST (voluntary registration, turnover under $75k / $150k NFP), Before 1 July 2026 (quarterly system), Companies, Fringe Benefits Tax (FBT), From 1 July 2026 — Payday Super, GST & BAS (Business Activity Statement), Income Tax Returns (30 June balance date), Individuals & sole traders (+9 more)

### Community 88 - "Development Dependencies"
Cohesion: 0.18
Nodes (11): require-dev, laravel/boost, laravel/breeze, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision (+3 more)

### Community 89 - "TestCase"
Cohesion: 0.15
Nodes (5): CreatesApplication, Illuminate\Foundation\Testing\TestCase, TestCase, BankAccountStatementTest, FinancialReportsHubPageTest

### Community 90 - "main"
Cohesion: 0.29
Nodes (9): check_python(), install_dependencies(), main(), Ensure Python 3 is available., Install requirements.txt., Verify all required packages can be imported., Verify parser scripts exist., verify_imports() (+1 more)

### Community 92 - "DocumentCategory"
Cohesion: 0.13
Nodes (8): DocumentWorkspaceController, DocumentCategory, backfillCategoriesAndLabels(), up(), backfillNullLabels(), deduplicateLabels(), reassignOrphans(), up()

### Community 93 - "Tech Update Plans"
Cohesion: 0.14
Nodes (14): Comparison with Migration Manager CRM, Current stack (as of audit), Done, Executive summary, Out of scope (this plan), Plan, PR sizing guide, Pre-deploy checklist (each phase) (+6 more)

### Community 94 - "Laravel Framework Keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 95 - "Bank Statement Row Parsing"
Cohesion: 0.24
Nodes (8): build_entry(), parse_row_cells(), Build one statement row using fixed columns: date | description | amount_debit…, Remove trailing amount/balance tokens from a description (Macquarie compact…, should_skip_description(), strip_trailing_money(), FixedColumnsAllBanksTest, Balance moving 3,368.35DR -> 3,517.35DR is a debit, not a credit.

### Community 97 - "ReportEntityScopeResolver"
Cohesion: 0.23
Nodes (3): mergeReportFormScope(), ReportEntityScopeResolver, ReportEntityScopeResolverTest

### Community 99 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (6): AdminUsersWorkspaceController, resolveReportEntityIds(), EmailTemplatesWorkspaceController, PersonsIndexWorkspaceController, TableSort, Illuminate\Http\Request

### Community 100 - "Code Review Checklist"
Cohesion: 0.17
Nodes (11): A. Validation & HTTP input, B. Controllers & routing, C. Authorization, D. Eloquent & models, Detection Checklist, E. Architecture & organization, F. Frontend & views, G. Database & migrations (+3 more)

### Community 101 - "Convention Inference Guide"
Cohesion: 0.17
Nodes (11): Edge cases, Glob mapping, Ground Rules (read before you start), Infer Conventions, Process, Step 0: Orient, Step 1: Predefined sweep, Step 2: Open-ended pass (+3 more)

### Community 104 - "Tenant"
Cohesion: 0.13
Nodes (4): RealEstateCompany, RealEstateCompanyContact, Tenant, up()

### Community 106 - "Lodgement Reporting Logic"
Cohesion: 0.20
Nodes (10): Filters, Obligation types in scope (Phase 1), Part 4 — Proposed Report: ATO / ASIC Lodgement Status, Past-year gap detection logic, Purpose, Report columns, Status classification (locked for Phase 1), Suggested route (+2 more)

### Community 108 - "Asset Workspace Partials"
Cohesion: 0.29
Nodes (6): assets.partials.asset-details-sidebar, assets.partials.invoices-tab, assets.partials.linked-bank-accounts-show, assets.partials.loan-banking-show, business-entities.partials.compliance-workspace, business-entities.partials.documents-workspace

### Community 109 - "Composer Plugin Configuration"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 110 - "Compliance Category Migrations"
Cohesion: 0.48
Nodes (6): addLabelUniqueIndex(), backfillCategoriesAndFiles(), down(), dropLabelUniqueIndex(), seedCategoryGroups(), up()

### Community 111 - "Architecture Best Practices"
Cohesion: 0.17
Nodes (11): Architecture Best Practices, Code to Interfaces, Convention Over Configuration, Default Sort by Descending, Single-Purpose Action Classes, Use Atomic Locks for Race Conditions, Use `Concurrency::run()` for Parallel Execution, Use `Context` for Request-Scoped Data (+3 more)

### Community 112 - "Compliance Project Context"
Cohesion: 0.22
Nodes (9): Database tables supporting lodgement tracking, Existing compliance features, Existing reporting, How due dates work today, Part 2 — Current State in Asset Tracker, Project context, Provisioning behaviour (important for past-year reports), Report generation pattern (to reuse) (+1 more)

### Community 114 - "Laravel Configuration Standards"
Cohesion: 0.17
Nodes (10): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets, Consistency First, Decision Rules, How to Apply (+2 more)

### Community 115 - "Email Parsing API"
Cohesion: 0.40
Nodes (5): get, post, health(), parse_email(), UploadFile

### Community 116 - "Asset Form JavaScript"
Cohesion: 0.60
Nodes (5): bootAssetCreateForms(), initAssetCreateForm(), initAssetTypeToggle(), initCollapsibleSections(), scrollToFirstError()

### Community 117 - "Encrypted User Provider"
Cohesion: 0.60
Nodes (3): EncryptedEmailUserProvider, Illuminate\Auth\EloquentUserProvider, Illuminate\Contracts\Auth\Authenticatable

### Community 118 - "PSR-4 Autoload Mapping"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 119 - "Person Detail Views"
Cohesion: 0.40
Nodes (4): persons.partials.bank-accounts.list, persons.partials.person-bank-account-modal, persons.partials.roles-list, persons.partials.summary-stats

### Community 120 - "User Profile Views"
Cohesion: 0.40
Nodes (4): profile.partials.delete-user-form, profile.partials.two-factor-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 123 - "Layout Navigation Components"
Cohesion: 0.50
Nodes (3): bank-accounts.partials.bank-account-panel-shell, business-entities.partials.entity-workspace-panel, layouts.navigation

### Community 125 - "Account Purpose Migration"
Cohesion: 0.83
Nodes (3): alterAccountPurposeConstraint(), down(), up()

### Community 126 - "Email Hash Index Migration"
Cohesion: 0.83
Nodes (3): down(), indexExists(), up()

### Community 127 - "Mail Foreign Key Migration"
Cohesion: 0.83
Nodes (3): down(), hasUserForeignKey(), up()

### Community 128 - "Asset Creation Partials"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 129 - "Asset Editing Partials"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 130 - "Transaction Creation Partials"
Cohesion: 0.50
Nodes (3): partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 131 - "Transaction Editing Partials"
Cohesion: 0.50
Nodes (3): partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 135 - "Security Best Practices"
Cohesion: 0.17
Nodes (11): Audit Dependencies, Authorize Every Action, CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Keep Secrets Out of Code, Mass Assignment Protection, Prevent SQL Injection (+3 more)

### Community 136 - "EmailTemplate"
Cohesion: 0.13
Nodes (3): EmailTemplateController, EmailTemplate, EmailTemplatePolicy

### Community 158 - "Illuminate\View\View"
Cohesion: 0.09
Nodes (7): CarReportController, ProfileController, PropertyReportController, AppLayout, GuestLayout, Illuminate\View\Component, Illuminate\View\View

### Community 159 - "PHPUnit\Framework\TestCase"
Cohesion: 0.07
Nodes (8): ReportScopeQuery, PHPUnit\Framework\TestCase, AssetDueDateRemindersTest, AssetLoanAccountLinkTest, BankAccountAccessTest, BankAccountAssetLinkServiceTest, ReportScopeQueryTest, TwoFactorServiceTest

### Community 160 - "Queue Best Practices"
Cohesion: 0.18
Nodes (10): Always Implement `failed()`, Batch Related Jobs, Implement `ShouldBeUnique`, Queue & Job Best Practices, Rate Limit External API Calls in Jobs, `retryUntil()` Needs `$tries = 0`, Set `retry_after` Greater Than `timeout`, Use Exponential Backoff (+2 more)

### Community 163 - "Console Kernel Scheduling"
Cohesion: 0.29
Nodes (4): Kernel, Command, Illuminate\Console\Scheduling\Schedule, Illuminate\Foundation\Console\Kernel

### Community 164 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 186 - "Bug Tracking Notes"
Cohesion: 0.25
Nodes (7): Area 10 — Chart of accounts & vendors, CRM / Asset Tracker — Potential Bugs, Low, Medium, Notes, Re-verification changelog, Suggested fix priority (cross-area) — open items only

### Community 192 - "Frontend Refactoring Plan"
Cohesion: 0.25
Nodes (8): Current pain points, Phase 1a — Split workspace bundles, Phase 1b — Dashboard entry, Phase 1c — Entity & asset show entries, Phase 1d — Remaining inline scripts (incremental), Phase 1e — Optional lazy Tiptap tightening, Plan, Track 1: Vite page splitting & script extraction

### Community 212 - "UX Helper Plan"
Cohesion: 0.29
Nodes (7): Current state, Phase 2a — Flash component, Phase 2b — Toast helper (JS), Phase 2c — Confirm helper (JS + Blade), Phase 2d — Form confirm pattern (Blade), Plan, Track 2: UX helpers (flash, toast, confirm)

### Community 214 - "Deployment Documentation"
Cohesion: 0.22
Nodes (6): Deploy, Frontend build (Tailwind / Vite), Host, Post-deploy (required), Production server, Runtime versions (verified on server)

### Community 220 - "Icon Management Plan"
Cohesion: 0.33
Nodes (6): Current state, Phase 3a — CI / npm wiring, Phase 3b — JS dynamic icons (when needed), Phase 3c — Ongoing, Plan, Track 3: Icons — guardrails & dynamic JS

### Community 457 - "System Feature Areas"
Cohesion: 0.33
Nodes (6): Accounting, Asset management, Business entities, Documents & communication, Features, Security & access

### Community 458 - "Advanced Query Best Practices"
Cohesion: 0.20
Nodes (9): Advanced Query Patterns, Create Dynamic Relationships via Subquery FK, Prefer `whereIn` + Subquery Over `whereHas`, Sometimes Two Simple Queries Beat One Complex Query, Use `addSelect()` Subqueries for Single Values from Has-Many, Use Compound Indexes Matching `orderBy` Column Order, Use Conditional Aggregates Instead of Multiple Count Queries, Use Correlated Subqueries for Has-Many Ordering (+1 more)

### Community 459 - "Invoices and Rent Area"
Cohesion: 0.50
Nodes (4): Area 11 — Invoices & rent, High, Low, Medium

### Community 460 - "Commitments and Tracking Area"
Cohesion: 0.50
Nodes (4): Area 12 — Commitments & tracking, High, Low, Medium

### Community 461 - "Financial Reporting Area"
Cohesion: 0.50
Nodes (4): Area 13 — Financial & property reports, High, Low, Medium

### Community 462 - "Email and Templates Area"
Cohesion: 0.50
Nodes (4): Area 14 — Email & templates, High, Low, Medium

### Community 463 - "Reminders and Contacts Area"
Cohesion: 0.50
Nodes (4): Area 15 — Reminders / contact lists, High, Low, Medium

### Community 464 - "API and Policies Area"
Cohesion: 0.50
Nodes (4): Area 16 — Cross-cutting / API / policies, High, Low, Medium

### Community 465 - "Auth and Security Area"
Cohesion: 0.50
Nodes (4): Area 1 — Auth & security, High, Low, Medium

### Community 466 - "Admin Management Area"
Cohesion: 0.50
Nodes (4): Area 2 — Admin user management, High, Low, Medium

### Community 467 - "Dashboard and Profile Area"
Cohesion: 0.50
Nodes (4): Area 3 — Dashboard & profile, High, Low, Medium

### Community 468 - "Business Entities Area"
Cohesion: 0.50
Nodes (4): Area 4 — Business entities, High, Low, Medium

### Community 469 - "Persons and Roles Area"
Cohesion: 0.50
Nodes (4): Area 5 — Persons & roles, High, Low, Medium

### Community 470 - "Asset Lifecycle Area"
Cohesion: 0.50
Nodes (4): Area 6 — Assets CRUD & lifecycle, High, Low, Medium

### Community 471 - "Documents Area"
Cohesion: 0.50
Nodes (4): Area 7 — Documents, High, Low, Medium

### Community 472 - "Compliance Risk Levels"
Cohesion: 0.50
Nodes (4): Area 8 — Compliance, High, Low, Medium

### Community 473 - "Banking Transaction Categories"
Cohesion: 0.50
Nodes (4): Area 9 — Banking & transactions, High, Low, Medium

### Community 474 - "MCP Configuration"
Cohesion: 0.22
Nodes (8): context7, graphify, laravel-boost, CONTEXT7_API_KEY, npx, php, py, @upstash/context7-mcp

### Community 475 - "ComplianceDocumentFile"
Cohesion: 0.15
Nodes (3): ComplianceController, ComplianceDocumentFile, ComplianceUploadService

### Community 477 - "Database Performance Best Practices"
Cohesion: 0.20
Nodes (9): Add Database Indexes, Always Eager Load Relationships, Chunk Large Datasets, Database Performance Best Practices, No Queries in Blade Templates, Prevent Lazy Loading in Development, Select Only Needed Columns, Use `cursor()` for Memory-Efficient Iteration (+1 more)

### Community 480 - "Events and Notifications Best Practices"
Cohesion: 0.20
Nodes (9): Always Queue Notifications, Events & Notifications Best Practices, Implement `HasLocalePreference` on Notifiable Models, Rely on Event Discovery, Route Notification Channels to Dedicated Queues, Run `event:cache` in Production Deploy, Use `afterCommit()` on Notifications in Transactions, Use On-Demand Notifications for Non-User Recipients (+1 more)

### Community 496 - "Caching Best Practices"
Cohesion: 0.22
Nodes (8): Caching Best Practices, Configure Failover Cache Stores in Production, Use `Cache::add()` for Atomic Conditional Writes, Use `Cache::flexible()` for Stale-While-Revalidate, Use `Cache::memo()` to Avoid Redundant Hits Within a Request, Use `Cache::remember()` Instead of Manual Get/Put, Use Cache Tags to Invalidate Related Groups, Use `once()` for Per-Request Memoization

### Community 497 - "Eloquent Best Practices"
Cohesion: 0.22
Nodes (8): Apply Global Scopes Sparingly, Avoid Hardcoded Table Names in Queries, Cast Date Columns Properly, Define Attribute Casts, Eloquent Best Practices, Use Correct Relationship Types, Use Local Scopes for Reusable Queries, Use `whereBelongsTo()` for Relationship Queries

### Community 498 - "Migration Best Practices"
Cohesion: 0.22
Nodes (8): Add Indexes in the Migration, Generate Migrations with Artisan, Keep Migrations Focused, Migration Best Practices, Mirror Defaults in Model `$attributes`, Never Modify Deployed Migrations, Use `constrained()` for Foreign Keys, Write Reversible `down()` Methods by Default

### Community 499 - "Currency Parsing Utilities"
Cohesion: 0.26
Nodes (11): Any, Decimal, balance_or_none(), money_or_none(), money_values_from_text(), parse_amount(), parse_text_block(), parse_westpac_text_block() (+3 more)

### Community 501 - "System Architecture Overview"
Cohesion: 0.50
Nodes (4): Architecture, Key models, Notable services, Stack

### Community 507 - "Statement Date Parsing"
Cohesion: 0.24
Nodes (6): adjust_year(), extract_year_hint_from_text(), is_plausible_statement_year(), parse_generic_text_block(), Pull a year from statement-period headers (avoid FY / amount false positives)., StatementDateParsingTest

### Community 511 - "EncryptedFilesystemAdapter"
Cohesion: 0.26
Nodes (3): EncryptedFilesystemAdapter, Illuminate\Filesystem\FilesystemAdapter, League\Flysystem\Filesystem

### Community 512 - "Blade View Best Practices"
Cohesion: 0.25
Nodes (7): Blade & Views Best Practices, Prefer Blade Components Over `@include`, Use `$attributes->merge()` in Component Templates, Use `@aware` for Deeply Nested Component Props, Use Blade Fragments for Partial Re-Renders (htmx/Turbo), Use `@pushOnce` for Per-Component Scripts, Use View Composers for Shared View Data

### Community 513 - "Error Handling Best Practices"
Cohesion: 0.25
Nodes (7): Add Context to Exception Classes, Enable `dontReportDuplicates()`, Error Handling Best Practices, Exception Reporting and Rendering, Force JSON Error Rendering for API Routes, Throttle High-Volume Exceptions, Use `ShouldntReport` for Exceptions That Should Never Log

### Community 515 - "Task Scheduling Best Practices"
Cohesion: 0.25
Nodes (7): Task Scheduling Best Practices, Use `environments()` to Restrict Tasks, Use `onOneServer()` on Multi-Server Deployments, Use `runInBackground()` for Concurrent Long Tasks, Use Schedule Groups for Shared Configuration, Use `takeUntilTimeout()` for Time-Bounded Processing, Use `withoutOverlapping()` on Variable-Duration Tasks

### Community 516 - "Testing Best Practices"
Cohesion: 0.25
Nodes (7): Call `Event::fake()` After Factory Setup, Testing Best Practices, Use `Exceptions::fake()` to Assert Exception Reporting, Use Factory States and Sequences, Use `LazilyRefreshDatabase` Over `RefreshDatabase`, Use Model Assertions Over Raw Database Assertions, Use `recycle()` to Share Relationship Instances Across Factories

### Community 519 - "Collection Best Practices"
Cohesion: 0.29
Nodes (6): Choose `cursor()` vs. `lazy()` Correctly, Collection Best Practices, Use `#[CollectedBy]` for Custom Collection Classes, Use Higher-Order Messages for Simple Operations, Use `lazyById()` When Updating Records While Iterating, Use `toQuery()` for Bulk Operations on Collections

### Community 520 - "HTTP Client Best Practices"
Cohesion: 0.29
Nodes (6): Always Set Explicit Timeouts, Fake HTTP Calls in Tests, Handle Errors Explicitly, HTTP Client Best Practices, Use Request Pooling for Concurrent Requests, Use Retry with Backoff for External APIs

### Community 521 - "Mail Best Practices"
Cohesion: 0.29
Nodes (6): Implement `ShouldQueue` on the Mailable Class, Mail Best Practices, Separate Content Tests from Sending Tests, Use `afterCommit()` on Mailables Inside Transactions, Use `assertQueued()` Not `assertSent()` for Queued Mailables, Use Markdown Mailables for Transactional Emails

### Community 522 - "Routing Best Practices"
Cohesion: 0.29
Nodes (6): Keep Controllers Thin, Routing & Controllers Best Practices, Type-Hint Form Requests, Use Implicit Route Model Binding, Use Resource Controllers, Use Scoped Bindings for Nested Resources

### Community 523 - "Code Style Conventions"
Cohesion: 0.29
Nodes (6): Conventions & Style, Follow Laravel Naming Conventions, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers

### Community 524 - "Validation Best Practices"
Cohesion: 0.29
Nodes (6): Always Use `validated()`, Array vs. String Notation for Rules, Use Form Request Classes, Use `Rule::when()` for Conditional Validation, Use the `after()` Method for Custom Validation, Validation & Forms Best Practices

### Community 535 - "PDF Parser Integration"
Cohesion: 0.31
Nodes (9): Path, decrypt_pdf_if_needed(), detect_bank(), emit(), extract_entries(), main(), Return (path, error). If the PDF is encrypted with a non-empty password, return…, Always emit JSON on stdout so Laravel can decode failures reliably. (+1 more)

### Community 536 - "Transaction Text Grouping"
Cohesion: 0.33
Nodes (8): group_transaction_text_blocks(), group_westpac_text_blocks(), is_continuation_text_line(), is_month_year_header(), is_westpac_continuation_text(), line_starts_with_date(), True when a non-dated line is a Westpac wrap fragment, not footer/boilerplate., True when a non-dated line continues the previous transaction row.

### Community 539 - "Email Message Parser"
Cohesion: 0.36
Nodes (7): format_date(), main(), parse_recipients(), parse_sender(), Extract name and email from sender (string or object)., Extract recipients as list of strings., Format datetime to ISO string.

## Knowledge Gaps
- **541 isolated node(s):** `py`, `npx`, `@upstash/context7-mcp`, `CONTEXT7_API_KEY`, `php` (+536 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **102 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `BusinessEntity` connect `BusinessEntity` to `EncryptsAttributes.php`, `ComplianceCategory`, `Reminder`, `Illuminate\Http\JsonResponse`, `Invoice`, `AssetsWorkspaceController`, `EnsuresOperationalBusinessEntity.php`, `Asset`, `AssetController`, `AppServiceProvider.php`, `.storeBankAccount`, `EntityPerson`, `ComplianceYearRecord`, `Carbon\Carbon`, `BankImportController`, `Illuminate\View\View`, `User`, `Lease`, `self`, `FinancialReportService`, `Commitment`, `BankAccountStatement`, `ComplianceReportController`, `BusinessEntityController`, `MailMessage`, `BusinessEntityController.php`, `ASIC Renewal Tests`, `.rule`, `Transaction.php`, `MailMessageController`, `BankAccountAssetLinkService`, `Illuminate\Support\Collection`, `Illuminate\Console\Command`, `BankStatementEntry`, `BankAccount`, `BusinessEntityRegistrationDateTest`, `Illuminate\Database\Eloquent\Model`, `Address Formatting Utility`, `JournalEntry`, `Document`, `FinancialYear`, `ComplianceDocumentFile`, `DocumentCategory`, `Illuminate\Http\Request`, `BusinessEntity.php`, `.update`, `Tenant`, `.canAccessEntity`?**
  _High betweenness centrality (0.122) - this node is a cross-community bridge._
- **Why does `BankAccount` connect `BankAccount` to `EncryptsAttributes.php`, `BankAccountFormTest`, `AssetSummaryReportService`, `Reminder`, `DocumentStorage`, `Illuminate\Http\JsonResponse`, `Invoice`, `Asset`, `AssetController`, `.storeBankAccount`, `EntityPerson`, `BankAccountMaskingTest`, `self`, `BankAccountStatement`, `BusinessEntityController`, `BusinessEntityController.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `BankAccountAssetLinkService`, `BankStatementEntry`, `Illuminate\Database\Eloquent\Model`, `Illuminate\Database\Eloquent\Relations\HasMany`, `.businessEntity`, `Illuminate\Http\Request`, `BusinessEntity.php`, `.update`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Why does `Asset` connect `Asset` to `BusinessEntity`, `AssetSummaryReportService`, `Reminder`, `Illuminate\Http\JsonResponse`, `AssetsWorkspaceController`, `EnsuresOperationalBusinessEntity.php`, `AssetController`, `AppServiceProvider.php`, `ComplianceYearRecord`, `User`, `Illuminate\View\View`, `PHPUnit\Framework\TestCase`, `Lease`, `Commitment`, `BankAccountStatement`, `BusinessEntityController`, `MailMessage`, `.rule`, `Transaction.php`, `MailMessageController`, `BankAccountAssetLinkService`, `Illuminate\Support\Collection`, `PropertyReportService`, `Illuminate\Database\Eloquent\Model`, `JournalEntry`, `Document`, `ComplianceDocumentFile`, `Illuminate\Http\Request`, `BusinessEntity.php`, `.update`, `Tenant`, `CarReportService`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **Are the 44 inferred relationships involving `BusinessEntity` (e.g. with `.handle()` and `.listHtmlForContext()`) actually correct?**
  _`BusinessEntity` has 44 INFERRED edges - model-reasoned connections that need verification._
- **Are the 24 inferred relationships involving `BankAccount` (e.g. with `.bankAccountPickerData()` and `.syncBankAccountLinks()`) actually correct?**
  _`BankAccount` has 24 INFERRED edges - model-reasoned connections that need verification._
- **Are the 25 inferred relationships involving `Asset` (e.g. with `.assetOperationalQuery()` and `.dashboard()`) actually correct?**
  _`Asset` has 25 INFERRED edges - model-reasoned connections that need verification._
- **What connects `py`, `npx`, `@upstash/context7-mcp` to the rest of the system?**
  _541 weakly-connected nodes found - possible documentation gaps or missing edges._