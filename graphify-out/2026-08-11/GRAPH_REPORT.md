# Graph Report - .  (2026-08-11)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 3914 nodes · 8558 edges · 537 communities (428 shown, 109 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 476 edges (avg confidence: 0.78)
- Token cost: 21,039 input · 6,306 output

## Graph Freshness
- Built from commit: `0787295c`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Business Entity Management
- Attribute Encryption Trait
- Bank Account Linking
- API Resource Transformers
- User Management Workspace
- Encrypted Backup Service
- Admin Workspace Scripts
- Bank Transaction Importing
- Task and Due Date Tracking
- Authentication Controllers
- Document Storage System
- Compliance Workspace Frontend
- Portfolio and Profile Workspaces
- Gmail and Invoice Sync
- Entity Form Frontend
- Frontend Dependencies
- TomSelect Dropdown Initialization
- Tracking Category Management
- Email Template Management
- Asset Model Logic
- Data Encryption Helpers
- Chart of Accounts Controller
- Document Upload Controller
- Bank Modal Frontend
- Entity Person Management
- Compliance Record Policies
- ASIC and Compliance Logic
- Transaction Model Logic
- Vendor Management
- User Model Logic
- Financial Reporting Service
- Document Workspace Controller
- Lease and Rent Invoicing
- Commitment and Payment Tracking
- Laravel Development Guidelines
- Mail Message Controller
- Compliance Document Handling
- Bank Statement Controller
- Compliance and Data Maintenance
- Business Entity Controller
- Mail and Attachment Models
- Eloquent Relationships
- Database Migrations
- ASIC Renewal Testing
- Bank PDF Parsing Logic
- System Module Overview
- Real Estate Tenant Management
- Entity Relationship Models
- Transaction Payment Frontend
- Director Loan Reporting
- Bank Account Security
- Transaction Posting Service
- Split Transaction Logic
- Rich Text Editor Config
- Contact List Management
- Two-Factor Authentication Service
- Form Request Validation
- Persons Workspace Controller
- Compliance Reporting Service
- Note Management
- Document Upload Validation
- Property Reporting Service
- Composer Scripts
- Artisan Maintenance Commands
- Project Rules Index
- Bank Account Relations
- Pest Testing Documentation
- Gmail API Fetcher
- Compliance Reminder Sync
- Document Migration Services
- Entity Registration Testing
- Tailwind CSS Documentation
- Financial Report Controller
- Australian Address Formatting
- Security Middleware
- Blade Template Partials
- PDF Text Extraction Logic
- Form UI Helpers
- Invoice Posting Service
- Depreciation Posting Service
- Compliance Implementation Plan
- Document Upload Service
- Statement Upload Service
- Composer Project Config
- PHP Package Dependencies
- Assets Workspace Controller
- Account Display Attributes
- ATO Filing Reference
- Development Dependencies
- Bank Reconciliation Testing
- Python Environment Setup
- Document Category Management
- Person Workspace Scripts
- Project Audit and Roadmap
- Laravel Framework Metadata
- Statement Row Parsing
- Asset Show Controller
- Person Index Workspace
- Chart of Accounts Documentation
- Reminder Authorization Policies
- Code Review Checklist
- Convention Inference Guide
- Asset and Lease Management
- Security Audit Service
- Workspace and Report Controllers
- Security Configuration Setup
- Lodgement Reporting Phase 1
- Asset and Entity Partials
- Composer Configuration
- Compliance Category Migrations
- Architecture Best Practices
- Compliance Tracking Context
- Encrypted Backup Restoration
- Laravel Best Practices
- Email Service API
- Asset Form Scripts
- Encrypted User Authentication
- Autoload Configuration
- Person Detail Views
- User Profile Views
- Event Service Provider
- Route Service Provider
- Layout and Navigation
- Entity Encryption Migration
- Account Purpose Migration
- Email Hash Index Migration
- Mail Foreign Key Migration
- Asset Creation Views
- Asset Editing Views
- Transaction Creation Views
- Transaction Editing Views
- HTTP Kernel
- Password Security Policy
- Bank Account UI Components
- Security Best Practices
- Asset and Car Reporting
- Report Scope Queries
- Bank Statement Parsing
- Property Report Tests
- Queue Best Practices
- Console Command Scheduling
- Workspace Notification Helpers
- Core Entity Models
- Compliance Year Logic Tests
- Bug Tracking and Priority
- Frontend Refactoring Plan
- Document Authorization Policies
- UX Helper Plan
- Deployment Documentation
- Database Seeders
- Due Date Reminder Tests
- Icon Management Plan
- Transaction Dashboard UI
- Global Search Navigation
- Person Management UI
- Bank Account Creation
- Bank Account Editing
- Entity Bank Forms
- Portfolio Creation Forms
- Portfolio Editing Forms
- Business Bank Creation
- Business Bank Editing
- Business Bank Forms
- Person Bank Forms
- Bank Account Forms
- Vendor Management UI
- User Management Views
- User Row Actions
- Lease Management Forms
- Tenant Management Forms
- Bank Account Selection
- Banking Detail Toggles
- Portfolio Bank Accounts
- Business Asset Workspace
- Contact List Workspace
- Business Notes Workspace
- Person Management Workspace
- Email Template Management
- Email Template Actions
- Transaction Detail Reports
- Financial Report Filters
- Portfolio Property Filters
- Financial Property Filters
- Python Dependency Script
- Email Service Script
- Application Startup Script
- Asset Due Dates
- Asset Index View
- Asset Sidebar Details
- Bank Account Actions
- Loan Banking Fields
- Bank Account Configuration
- Portfolio Holder List
- Business Entity Creation
- Rent Collection Fields
- Business Bank Accounts
- Rent Asset Management
- Person Account Actions
- Person List Actions
- Profile Creation Fields
- Commitment Creation Form
- Commitment Edit Form
- ATO Lodgement Warnings
- Compliance Date Warnings
- Person Bank Accounts
- Bank Account Modal
- Vendor Creation Form
- System Feature Areas
- Advanced Query Best Practices
- Invoices and Rent Priority
- Commitments Tracking Priority
- Financial Reporting Priority
- Email Templates Priority
- Reminders and Contacts Priority
- API and Policies Priority
- Auth and Security Priority
- Admin Management Priority
- Dashboard and Profile Priority
- Business Entity Priority
- Persons and Roles Priority
- Asset Lifecycle Priority
- Document Management Priority
- Compliance Priority
- Banking and Transactions Priority
- MCP API Integration
- Encrypted Filesystem Adapter
- Commitment Authorization Policies
- Database Performance Best Practices
- Events and Notifications Guide
- Rent Link Management
- Bank Statement Panel
- Caching Best Practices
- Eloquent Best Practices
- Migration Best Practices
- Money Parsing Utilities
- System Architecture Overview
- Reconciliation Match Panel
- Bank Reconciliation UI
- Statement Date Extraction
- Report Scope Resolvers
- Blade and Views Guide
- Error Handling Best Practices
- Task Scheduling Best Practices
- Testing Best Practices
- Collection Best Practices
- HTTP Client Best Practices
- Mail Best Practices
- Routing and Controllers Guide
- Coding Style Conventions
- Validation Best Practices
- PDF Bank Detection
- Transaction Text Processing
- Email Message Parsing
- Laravel Package Configuration
- Transaction Management Panel

## God Nodes (most connected - your core abstractions)
1. `BusinessEntity` - 484 edges
2. `BankAccount` - 196 edges
3. `Asset` - 155 edges
4. `Transaction` - 119 edges
5. `BusinessEntityController` - 113 edges
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

## Communities (537 total, 109 thin omitted)

### Community 0 - "Business Entity Management"
Cohesion: 0.04
Nodes (6): BankAccountsWorkspaceController, ensureNotClosed(), ensureOperationalForAccounting(), BusinessEntity, BankAccountAssetLinkService, LoanOffsetTransactionGuard

### Community 1 - "Attribute Encryption Trait"
Cohesion: 0.38
Nodes (11): addEncryptedAttribute(), attributesToArray(), decrypt(), decryptAttributes(), encrypt(), encryptAttributes(), getAttribute(), getEncryptedAttributes() (+3 more)

### Community 3 - "API Resource Transformers"
Cohesion: 0.11
Nodes (9): AssetResource, ComplianceCategoryResource, ComplianceDocumentFileResource, ComplianceDocumentTypeResource, ComplianceYearWorkspaceResource, ContactListResource, DocumentCategoryResource, DocumentSlotResource (+1 more)

### Community 4 - "User Management Workspace"
Cohesion: 0.09
Nodes (6): AdminUsersWorkspaceController, UserManagementController, EmailTemplateController, EmailTemplatesWorkspaceController, EntityShowWorkspaceController, Illuminate\Http\JsonResponse

### Community 6 - "Admin Workspace Scripts"
Cohesion: 0.17
Nodes (43): alertHttpError(), boot(), initAdminUsersWorkspace(), pageQuery(), withPageQuery(), workspaceUrl(), initAssetShowWorkspace(), alertHttpError() (+35 more)

### Community 7 - "Bank Transaction Importing"
Cohesion: 0.06
Nodes (9): BankAccountImportController, BankAccountTransactionController, BankStatementEntry, BankCsvStatementParser, BankStatementApplyService, BankStatementMatchSuggester, Carbon, BankStatementParseService (+1 more)

### Community 8 - "Task and Due Date Tracking"
Cohesion: 0.07
Nodes (5): BillsTasksController, Collection, ReminderController, Reminder, Illuminate\Database\Eloquent\Builder

### Community 9 - "Authentication Controllers"
Cohesion: 0.06
Nodes (20): AuthenticatedSessionController, ConfirmablePasswordController, EmailVerificationNotificationController, EmailVerificationPromptController, NewPasswordController, PasswordController, PasswordResetLinkController, TwoFactorController (+12 more)

### Community 10 - "Document Storage System"
Cohesion: 0.11
Nodes (5): DocumentStorage, UserFactory, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 11 - "Compliance Workspace Frontend"
Cohesion: 0.10
Nodes (44): alertHttpError(), alertValidationErrors(), api(), bindTabActivation(), bootWorkspaces(), buildCategoryPanel(), buildFileRow(), buildStatusCell() (+36 more)

### Community 12 - "Portfolio and Profile Workspaces"
Cohesion: 0.14
Nodes (3): BankAccountPanelController, PersonShowWorkspaceController, Person

### Community 13 - "Gmail and Invoice Sync"
Cohesion: 0.07
Nodes (16): GmailSyncCommand, InvoiceController, SyncGmailForUser, ContactEmail, InvoiceReminderMail, Invoice, InvoiceLine, InvoicePaymentService (+8 more)

### Community 14 - "Entity Form Frontend"
Cohesion: 0.11
Nodes (32): bootApp(), exposeRichTextHelpers(), loadRichTextModule(), initEntityCreateForm(), initEntityFormFields(), setCompanyFieldsState(), setInputDisabled(), setRegistrationDateFieldState() (+24 more)

### Community 15 - "Frontend Dependencies"
Cohesion: 0.05
Nodes (39): alpinejs, concurrently, flatpickr, laravel-vite-plugin, dependencies, flatpickr, @tiptap/core, @tiptap/extension-color (+31 more)

### Community 16 - "TomSelect Dropdown Initialization"
Cohesion: 0.15
Nodes (31): tom-select, activateTomSelectsIn(), addNativeOptionToTomSelect(), bindDropdownReposition(), buildOptions(), createTomSelect(), dropdownScrollRoots(), forceActivateTomSelectsIn() (+23 more)

### Community 17 - "Tracking Category Management"
Cohesion: 0.11
Nodes (4): TrackingCategoryController, TrackingSubCategoryController, TrackingCategory, TrackingSubCategory

### Community 19 - "Asset Model Logic"
Cohesion: 0.09
Nodes (3): Asset, down(), up()

### Community 20 - "Data Encryption Helpers"
Cohesion: 0.20
Nodes (3): BackfillPersonsEncryption, ReencryptModelAttributes, EncryptionHelper

### Community 21 - "Chart of Accounts Controller"
Cohesion: 0.10
Nodes (7): ChartOfAccountController, BankStatementPdfTestController, PropertyReportController, AppLayout, GuestLayout, Illuminate\View\Component, Illuminate\View\View

### Community 23 - "Bank Modal Frontend"
Cohesion: 0.10
Nodes (26): availablePurposes(), bindCollapsibleFilters(), bindTransactionsPanel(), buildCreateFormUrl(), getSelectedAccountOption(), getSelectValue(), initBankAccountModal(), initBankTransactionsPage() (+18 more)

### Community 24 - "Entity Person Management"
Cohesion: 0.08
Nodes (4): EntityPersonController, EntityPerson, HeaderSearchIndex, TransactionPayerResolver

### Community 25 - "Compliance Record Policies"
Cohesion: 0.12
Nodes (4): ComplianceYearRecord, ComplianceYearRecordPolicy, ComplianceYearService, Carbon

### Community 26 - "ASIC and Compliance Logic"
Cohesion: 0.14
Nodes (7): Carbon, ComplianceDocumentType, AtoDueDateService, Carbon, Carbon\Carbon, Carbon\CarbonInterface, up()

### Community 27 - "Transaction Model Logic"
Cohesion: 0.08
Nodes (3): Transaction, TransactionObserver, makeTransaction()

### Community 28 - "Vendor Management"
Cohesion: 0.15
Nodes (3): VendorController, Vendor, VendorSyncService

### Community 29 - "User Model Logic"
Cohesion: 0.08
Nodes (4): User, AssetPolicy, BusinessEntityPolicy, Illuminate\Foundation\Auth\User

### Community 30 - "Financial Reporting Service"
Cohesion: 0.11
Nodes (3): JournalLine, FinancialReportService, Illuminate\Database\Eloquent\Collection

### Community 32 - "Lease and Rent Invoicing"
Cohesion: 0.11
Nodes (5): AssetInvoiceController, RentInvoiceController, Lease, Carbon, RentInvoiceService

### Community 34 - "Laravel Development Guidelines"
Cohesion: 0.07
Nodes (27): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+19 more)

### Community 35 - "Mail Message Controller"
Cohesion: 0.17
Nodes (3): MailMessageController, Closure, MailLabel

### Community 36 - "Compliance Document Handling"
Cohesion: 0.06
Nodes (10): ComplianceController, ComplianceWorkspaceController, ComplianceCategory, ComplianceDocumentFile, ComplianceDocumentFilePolicy, ChecklistFilenameMatcher, ComplianceFilenameMatcher, ComplianceUploadService (+2 more)

### Community 38 - "Compliance and Data Maintenance"
Cohesion: 0.11
Nodes (4): ComplianceReportController, Carbon, Illuminate\Pagination\LengthAwarePaginator, Symfony\Component\HttpFoundation\StreamedResponse

### Community 40 - "Mail and Attachment Models"
Cohesion: 0.17
Nodes (4): MailAttachment, MailMessage, MsgParserService, Illuminate\Database\Eloquent\Relations\BelongsToMany

### Community 44 - "Bank PDF Parsing Logic"
Cohesion: 0.20
Nodes (21): ensure_westpac_row_width(), is_header_row(), is_westpac_continuation_row(), look_like_money(), merge_continuation_row(), merge_westpac_continuation(), merge_westpac_table_rows(), money_values_from_cells() (+13 more)

### Community 45 - "System Module Overview"
Cohesion: 0.10
Nodes (20): Accounting, API, Asset Tracker, Bank statements, Communication, Core, Creating users, Database (key tables) (+12 more)

### Community 46 - "Real Estate Tenant Management"
Cohesion: 0.14
Nodes (4): RealEstateCompany, RealEstateCompanyContact, Tenant, up()

### Community 47 - "Entity Relationship Models"
Cohesion: 0.07
Nodes (5): CommitmentPayment, EmailDraft, TransactionLine, Illuminate\Database\Eloquent\Relations\BelongsTo, BankAccountStatementTest

### Community 48 - "Transaction Payment Frontend"
Cohesion: 0.17
Nodes (19): bindBookingEntityChange(), bindPaidBySelectChange(), bindPaidByTomSelectChange(), bindPaymentStatusChange(), bookingEntityId(), initTransactionPaidByBankAccount(), parseEntityIdFromPaidBy(), refreshTransactionPaidByBankAccount() (+11 more)

### Community 51 - "Transaction Posting Service"
Cohesion: 0.16
Nodes (3): RepostPaidTransactionJournals, DateTimeInterface, TransactionPostingService

### Community 53 - "Rich Text Editor Config"
Cohesion: 0.26
Nodes (16): buildToolbar(), cleanupEditorShell(), createDivider(), createEditorShell(), createToolbarButton(), destroyRichTextEditor(), escapeSelectorId(), fieldValue() (+8 more)

### Community 54 - "Contact List Management"
Cohesion: 0.14
Nodes (4): ContactListController, ContactListsWorkspaceController, ContactList, ContactListPolicy

### Community 56 - "Form Request Validation"
Cohesion: 0.27
Nodes (3): LoginRequest, ProfileUpdateRequest, Illuminate\Foundation\Http\FormRequest

### Community 62 - "Composer Scripts"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 63 - "Artisan Maintenance Commands"
Cohesion: 0.17
Nodes (6): AddXeroChartOfAccounts, BackfillComplianceDueDates, EnsureComplianceYears, ScheduleBackup, Illuminate\Console\Command, Illuminate\Support\Facades\Schedule

### Community 64 - "Project Rules Index"
Cohesion: 0.40
Nodes (3): Project rules index, Loan vs offset accounts, Rule

### Community 65 - "Bank Account Relations"
Cohesion: 0.04
Nodes (3): BankAccount, BankAccountFormTest, BankAccountMaskingTest

### Community 66 - "Pest Testing Documentation"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 71 - "Tailwind CSS Documentation"
Cohesion: 0.14
Nodes (13): Basic Usage, Common Patterns, Common Pitfalls, CSS-First Configuration, Dark Mode, Documentation, Flexbox Layout, Grid Layout (+5 more)

### Community 74 - "Security Middleware"
Cohesion: 0.08
Nodes (14): EnsureAccountActive, EnsureSuperAdmin, Response, EnsureTwoFactorEnrolled, RateLimitMiddleware, SecurityHeaders, TwoFactorVerified, UniqueAbnHash (+6 more)

### Community 75 - "Blade Template Partials"
Cohesion: 0.15
Nodes (12): business-entities.partials.assets-workspace, business-entities.partials.bank-accounts.list, business-entities.partials.bank-import-summary, business-entities.partials.close-entity-modal, business-entities.partials.contact-lists-workspace, business-entities.partials.entity-details-sidebar, business-entities.partials.notes-workspace, business-entities.partials.persons-workspace (+4 more)

### Community 76 - "PDF Text Extraction Logic"
Cohesion: 0.11
Nodes (9): cells_from_text_line(), disambiguate_amount_balance(), extract_leading_date_from_line(), glue_money_suffix(), infer_sign_from_description(), Attach detached CR/DR markers to their amount: '607.06 Dr' -> '607.06Dr'., Compact PDF text often collapses blank debit/credit cells into: Date |…, Westpac text often keeps date+desc in one cell when amounts use wide gaps. (+1 more)

### Community 77 - "Form UI Helpers"
Cohesion: 0.18
Nodes (15): initAddressFieldSync(), syncAddressFieldsInForm(), commitDateFieldsInForm(), disableSubmitter(), ensureOverlay(), hideFormSaving(), initGlobalFormSaving(), isFormSaving() (+7 more)

### Community 79 - "Depreciation Posting Service"
Cohesion: 0.11
Nodes (5): PostMonthlyDepreciation, JournalEntry, DepreciationPostingService, DateTimeInterface, ManualJournalEntryService

### Community 80 - "Compliance Implementation Plan"
Cohesion: 0.14
Nodes (14): ATO Lodgement Tracking — Findings & Proposal, Current compliance config (`config/compliance.php`), Disclaimer, Executive Summary, Part 3 — Gaps, Part 5 — Implementation Plan, Part 6 — Configuration Reference, Part 7 — Recommendation (+6 more)

### Community 83 - "Composer Project Config"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 84 - "PHP Package Dependencies"
Cohesion: 0.18
Nodes (11): require, bacon/bacon-qr-code, fakerphp/faker, laravel/framework, laravel/tinker, league/flysystem, league/flysystem-aws-s3-v3, php (+3 more)

### Community 86 - "Account Display Attributes"
Cohesion: 0.05
Nodes (6): FinancialYear, Carbon, Exists, self, AtoDueDateServiceTest, FinancialYearTest

### Community 87 - "ATO Filing Reference"
Cohesion: 0.12
Nodes (17): Annual GST (voluntary registration, turnover under $75k / $150k NFP), Before 1 July 2026 (quarterly system), Companies, Fringe Benefits Tax (FBT), From 1 July 2026 — Payday Super, GST & BAS (Business Activity Statement), Income Tax Returns (30 June balance date), Individuals & sole traders (+9 more)

### Community 88 - "Development Dependencies"
Cohesion: 0.18
Nodes (11): require-dev, laravel/boost, laravel/breeze, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision (+3 more)

### Community 89 - "Bank Reconciliation Testing"
Cohesion: 0.10
Nodes (5): CreatesApplication, Illuminate\Database\Eloquent\Relations\Relation, Illuminate\Foundation\Testing\TestCase, TestCase, FinancialReportsHubPageTest

### Community 90 - "Python Environment Setup"
Cohesion: 0.29
Nodes (9): check_python(), install_dependencies(), main(), Ensure Python 3 is available., Install requirements.txt., Verify all required packages can be imported., Verify parser scripts exist., verify_imports() (+1 more)

### Community 91 - "Document Category Management"
Cohesion: 0.25
Nodes (6): backfillCategoriesAndLabels(), up(), backfillNullLabels(), deduplicateLabels(), reassignOrphans(), up()

### Community 92 - "Person Workspace Scripts"
Cohesion: 0.31
Nodes (8): alertHttpError(), boot(), initPersonForm(), initPersonsToggleLogic(), isSelectInVisibleSection(), refreshPersonFormSelects(), scheduleRefreshPersonFormSelects(), destroyTomSelect()

### Community 93 - "Project Audit and Roadmap"
Cohesion: 0.14
Nodes (14): Comparison with Migration Manager CRM, Current stack (as of audit), Done, Executive summary, Out of scope (this plan), Plan, PR sizing guide, Pre-deploy checklist (each phase) (+6 more)

### Community 94 - "Laravel Framework Metadata"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 95 - "Statement Row Parsing"
Cohesion: 0.24
Nodes (8): build_entry(), parse_row_cells(), Build one statement row using fixed columns: date | description | amount_debit…, Remove trailing amount/balance tokens from a description (Macquarie compact…, should_skip_description(), strip_trailing_money(), FixedColumnsAllBanksTest, Balance moving 3,368.35DR -> 3,517.35DR is a debit, not a credit.

### Community 97 - "Person Index Workspace"
Cohesion: 0.09
Nodes (3): PersonsIndexWorkspaceController, TableSort, TableSortTest

### Community 100 - "Code Review Checklist"
Cohesion: 0.17
Nodes (11): A. Validation & HTTP input, B. Controllers & routing, C. Authorization, D. Eloquent & models, Detection Checklist, E. Architecture & organization, F. Frontend & views, G. Database & migrations (+3 more)

### Community 101 - "Convention Inference Guide"
Cohesion: 0.17
Nodes (11): Edge cases, Glob mapping, Ground Rules (read before you start), Infer Conventions, Process, Step 0: Orient, Step 1: Predefined sweep, Step 2: Open-ended pass (+3 more)

### Community 104 - "Workspace and Report Controllers"
Cohesion: 0.09
Nodes (5): BankImportController, CarReportController, mergeReportFormScope(), resolveReportEntityIds(), Illuminate\Http\Request

### Community 106 - "Lodgement Reporting Phase 1"
Cohesion: 0.20
Nodes (10): Filters, Obligation types in scope (Phase 1), Part 4 — Proposed Report: ATO / ASIC Lodgement Status, Past-year gap detection logic, Purpose, Report columns, Status classification (locked for Phase 1), Suggested route (+2 more)

### Community 108 - "Asset and Entity Partials"
Cohesion: 0.29
Nodes (6): assets.partials.asset-details-sidebar, assets.partials.invoices-tab, assets.partials.linked-bank-accounts-show, assets.partials.loan-banking-show, business-entities.partials.compliance-workspace, business-entities.partials.documents-workspace

### Community 109 - "Composer Configuration"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 110 - "Compliance Category Migrations"
Cohesion: 0.48
Nodes (6): addLabelUniqueIndex(), backfillCategoriesAndFiles(), down(), dropLabelUniqueIndex(), seedCategoryGroups(), up()

### Community 111 - "Architecture Best Practices"
Cohesion: 0.17
Nodes (11): Architecture Best Practices, Code to Interfaces, Convention Over Configuration, Default Sort by Descending, Single-Purpose Action Classes, Use Atomic Locks for Race Conditions, Use `Concurrency::run()` for Parallel Execution, Use `Context` for Request-Scoped Data (+3 more)

### Community 112 - "Compliance Tracking Context"
Cohesion: 0.22
Nodes (9): Database tables supporting lodgement tracking, Existing compliance features, Existing reporting, How due dates work today, Part 2 — Current State in Asset Tracker, Project context, Provisioning behaviour (important for past-year reports), Report generation pattern (to reuse) (+1 more)

### Community 114 - "Laravel Best Practices"
Cohesion: 0.17
Nodes (10): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets, Consistency First, Decision Rules, How to Apply (+2 more)

### Community 115 - "Email Service API"
Cohesion: 0.40
Nodes (5): get, post, health(), parse_email(), UploadFile

### Community 116 - "Asset Form Scripts"
Cohesion: 0.60
Nodes (5): bootAssetCreateForms(), initAssetCreateForm(), initAssetTypeToggle(), initCollapsibleSections(), scrollToFirstError()

### Community 117 - "Encrypted User Authentication"
Cohesion: 0.60
Nodes (3): EncryptedEmailUserProvider, Illuminate\Auth\EloquentUserProvider, Illuminate\Contracts\Auth\Authenticatable

### Community 118 - "Autoload Configuration"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 119 - "Person Detail Views"
Cohesion: 0.40
Nodes (4): persons.partials.bank-accounts.list, persons.partials.person-bank-account-modal, persons.partials.roles-list, persons.partials.summary-stats

### Community 120 - "User Profile Views"
Cohesion: 0.40
Nodes (4): profile.partials.delete-user-form, profile.partials.two-factor-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 123 - "Layout and Navigation"
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

### Community 128 - "Asset Creation Views"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 129 - "Asset Editing Views"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 130 - "Transaction Creation Views"
Cohesion: 0.50
Nodes (3): partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 131 - "Transaction Editing Views"
Cohesion: 0.50
Nodes (3): partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 133 - "Password Security Policy"
Cohesion: 0.21
Nodes (4): PasswordSecurity, AppServiceProvider, PasswordPolicy, Illuminate\Validation\Rules\Password

### Community 135 - "Security Best Practices"
Cohesion: 0.17
Nodes (11): Audit Dependencies, Authorize Every Action, CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Keep Secrets Out of Code, Mass Assignment Protection, Prevent SQL Injection (+3 more)

### Community 136 - "Asset and Car Reporting"
Cohesion: 0.12
Nodes (6): AssetSummaryReportService, CarReportService, Carbon, CommitmentReportService, Carbon, Illuminate\Support\Collection

### Community 160 - "Queue Best Practices"
Cohesion: 0.18
Nodes (10): Always Implement `failed()`, Batch Related Jobs, Implement `ShouldBeUnique`, Queue & Job Best Practices, Rate Limit External API Calls in Jobs, `retryUntil()` Needs `$tries = 0`, Set `retry_after` Greater Than `timeout`, Use Exponential Backoff (+2 more)

### Community 163 - "Console Command Scheduling"
Cohesion: 0.29
Nodes (4): Kernel, Command, Illuminate\Console\Scheduling\Schedule, Illuminate\Foundation\Console\Kernel

### Community 165 - "Workspace Notification Helpers"
Cohesion: 0.19
Nodes (13): alertHttpError(), boot(), ensurePanelFormHandlers(), initFormPlugins(), panelFormHandlers, registerPanelFormHandler(), dismissToast(), escapeHtml() (+5 more)

### Community 180 - "Core Entity Models"
Cohesion: 0.15
Nodes (6): DepreciationSchedule, Email, AuthServiceProvider, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Foundation\Support\Providers\AuthServiceProvider

### Community 186 - "Bug Tracking and Priority"
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

### Community 215 - "Database Seeders"
Cohesion: 0.25
Nodes (4): ChartOfAccountSeeder, ComplianceDocumentTypeSeeder, DatabaseSeeder, Illuminate\Database\Seeder

### Community 220 - "Icon Management Plan"
Cohesion: 0.33
Nodes (6): Current state, Phase 3a — CI / npm wiring, Phase 3b — JS dynamic icons (when needed), Phase 3c — Ongoing, Plan, Track 3: Icons — guardrails & dynamic JS

### Community 457 - "System Feature Areas"
Cohesion: 0.33
Nodes (6): Accounting, Asset management, Business entities, Documents & communication, Features, Security & access

### Community 458 - "Advanced Query Best Practices"
Cohesion: 0.20
Nodes (9): Advanced Query Patterns, Create Dynamic Relationships via Subquery FK, Prefer `whereIn` + Subquery Over `whereHas`, Sometimes Two Simple Queries Beat One Complex Query, Use `addSelect()` Subqueries for Single Values from Has-Many, Use Compound Indexes Matching `orderBy` Column Order, Use Conditional Aggregates Instead of Multiple Count Queries, Use Correlated Subqueries for Has-Many Ordering (+1 more)

### Community 459 - "Invoices and Rent Priority"
Cohesion: 0.50
Nodes (4): Area 11 — Invoices & rent, High, Low, Medium

### Community 460 - "Commitments Tracking Priority"
Cohesion: 0.50
Nodes (4): Area 12 — Commitments & tracking, High, Low, Medium

### Community 461 - "Financial Reporting Priority"
Cohesion: 0.50
Nodes (4): Area 13 — Financial & property reports, High, Low, Medium

### Community 462 - "Email Templates Priority"
Cohesion: 0.50
Nodes (4): Area 14 — Email & templates, High, Low, Medium

### Community 463 - "Reminders and Contacts Priority"
Cohesion: 0.50
Nodes (4): Area 15 — Reminders / contact lists, High, Low, Medium

### Community 464 - "API and Policies Priority"
Cohesion: 0.50
Nodes (4): Area 16 — Cross-cutting / API / policies, High, Low, Medium

### Community 465 - "Auth and Security Priority"
Cohesion: 0.50
Nodes (4): Area 1 — Auth & security, High, Low, Medium

### Community 466 - "Admin Management Priority"
Cohesion: 0.50
Nodes (4): Area 2 — Admin user management, High, Low, Medium

### Community 467 - "Dashboard and Profile Priority"
Cohesion: 0.50
Nodes (4): Area 3 — Dashboard & profile, High, Low, Medium

### Community 468 - "Business Entity Priority"
Cohesion: 0.50
Nodes (4): Area 4 — Business entities, High, Low, Medium

### Community 469 - "Persons and Roles Priority"
Cohesion: 0.50
Nodes (4): Area 5 — Persons & roles, High, Low, Medium

### Community 470 - "Asset Lifecycle Priority"
Cohesion: 0.50
Nodes (4): Area 6 — Assets CRUD & lifecycle, High, Low, Medium

### Community 471 - "Document Management Priority"
Cohesion: 0.50
Nodes (4): Area 7 — Documents, High, Low, Medium

### Community 472 - "Compliance Priority"
Cohesion: 0.50
Nodes (4): Area 8 — Compliance, High, Low, Medium

### Community 473 - "Banking and Transactions Priority"
Cohesion: 0.50
Nodes (4): Area 9 — Banking & transactions, High, Low, Medium

### Community 474 - "MCP API Integration"
Cohesion: 0.22
Nodes (8): context7, graphify, laravel-boost, CONTEXT7_API_KEY, npx, php, py, @upstash/context7-mcp

### Community 475 - "Encrypted Filesystem Adapter"
Cohesion: 0.17
Nodes (5): EncryptedFilesystemAdapter, EncryptedFilesystemServiceProvider, Illuminate\Filesystem\FilesystemAdapter, Illuminate\Support\ServiceProvider, League\Flysystem\Filesystem

### Community 477 - "Database Performance Best Practices"
Cohesion: 0.20
Nodes (9): Add Database Indexes, Always Eager Load Relationships, Chunk Large Datasets, Database Performance Best Practices, No Queries in Blade Templates, Prevent Lazy Loading in Development, Select Only Needed Columns, Use `cursor()` for Memory-Efficient Iteration (+1 more)

### Community 480 - "Events and Notifications Guide"
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

### Community 499 - "Money Parsing Utilities"
Cohesion: 0.26
Nodes (11): Any, Decimal, balance_or_none(), money_or_none(), money_values_from_text(), parse_amount(), parse_text_block(), parse_westpac_text_block() (+3 more)

### Community 501 - "System Architecture Overview"
Cohesion: 0.50
Nodes (4): Architecture, Key models, Notable services, Stack

### Community 507 - "Statement Date Extraction"
Cohesion: 0.24
Nodes (6): adjust_year(), extract_year_hint_from_text(), is_plausible_statement_year(), parse_generic_text_block(), Pull a year from statement-period headers (avoid FY / amount false positives)., StatementDateParsingTest

### Community 510 - "Report Scope Resolvers"
Cohesion: 0.06
Nodes (9): ReportEntityScopeLabel, ReportEntityScopeResolver, PHPUnit\Framework\TestCase, AssetLoanAccountLinkTest, BankAccountAccessTest, BankAccountAssetLinkServiceTest, ReportEntityScopeLabelTest, ReportEntityScopeResolverTest (+1 more)

### Community 512 - "Blade and Views Guide"
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

### Community 522 - "Routing and Controllers Guide"
Cohesion: 0.29
Nodes (6): Keep Controllers Thin, Routing & Controllers Best Practices, Type-Hint Form Requests, Use Implicit Route Model Binding, Use Resource Controllers, Use Scoped Bindings for Nested Resources

### Community 523 - "Coding Style Conventions"
Cohesion: 0.29
Nodes (6): Conventions & Style, Follow Laravel Naming Conventions, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers

### Community 524 - "Validation Best Practices"
Cohesion: 0.29
Nodes (6): Always Use `validated()`, Array vs. String Notation for Rules, Use Form Request Classes, Use `Rule::when()` for Conditional Validation, Use the `after()` Method for Custom Validation, Validation & Forms Best Practices

### Community 535 - "PDF Bank Detection"
Cohesion: 0.31
Nodes (9): Path, decrypt_pdf_if_needed(), detect_bank(), emit(), extract_entries(), main(), Return (path, error). If the PDF is encrypted with a non-empty password, return…, Always emit JSON on stdout so Laravel can decode failures reliably. (+1 more)

### Community 536 - "Transaction Text Processing"
Cohesion: 0.33
Nodes (8): group_transaction_text_blocks(), group_westpac_text_blocks(), is_continuation_text_line(), is_month_year_header(), is_westpac_continuation_text(), line_starts_with_date(), True when a non-dated line is a Westpac wrap fragment, not footer/boilerplate., True when a non-dated line continues the previous transaction row.

### Community 539 - "Email Message Parsing"
Cohesion: 0.36
Nodes (7): format_date(), main(), parse_recipients(), parse_sender(), Extract name and email from sender (string or object)., Extract recipients as list of strings., Format datetime to ISO string.

### Community 545 - "Laravel Package Configuration"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **544 isolated node(s):** `py`, `npx`, `@upstash/context7-mcp`, `CONTEXT7_API_KEY`, `php` (+539 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **109 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `BusinessEntity` connect `Business Entity Management` to `Attribute Encryption Trait`, `Bank Account Linking`, `API Resource Transformers`, `User Management Workspace`, `Password Security Policy`, `Bank Transaction Importing`, `Task and Due Date Tracking`, `Authentication Controllers`, `Asset and Car Reporting`, `Portfolio and Profile Workspaces`, `Gmail and Invoice Sync`, `Tracking Category Management`, `Chart of Accounts Controller`, `Document Upload Controller`, `Entity Person Management`, `Compliance Record Policies`, `ASIC and Compliance Logic`, `User Model Logic`, `Financial Reporting Service`, `Document Workspace Controller`, `Lease and Rent Invoicing`, `Commitment and Payment Tracking`, `Mail Message Controller`, `Bank Account Request Handling`, `Compliance Document Handling`, `Compliance and Data Maintenance`, `Business Entity Controller`, `Mail and Attachment Models`, `ASIC Renewal Testing`, `Real Estate Tenant Management`, `Director Loan Reporting`, `Bank Account Security`, `Core Entity Models`, `Contact List Management`, `Persons Workspace Controller`, `Compliance Reporting Service`, `Note Management`, `Artisan Maintenance Commands`, `Bank Account Relations`, `Document Authorization Policies`, `Document Migration Services`, `Entity Registration Testing`, `Financial Report Controller`, `Australian Address Formatting`, `Depreciation Posting Service`, `Document Upload Service`, `Assets Workspace Controller`, `Account Display Attributes`, `Asset Show Controller`, `Person Index Workspace`, `Asset and Lease Management`, `Workspace and Report Controllers`?**
  _High betweenness centrality (0.127) - this node is a cross-community bridge._
- **Why does `BankAccount` connect `Bank Account Relations` to `Business Entity Management`, `Attribute Encryption Trait`, `Bank Account Linking`, `Bank Transaction Importing`, `Task and Due Date Tracking`, `Asset and Car Reporting`, `Document Storage System`, `Portfolio and Profile Workspaces`, `Gmail and Invoice Sync`, `Asset Model Logic`, `Entity Person Management`, `Bank Account Request Handling`, `Bank Statement Controller`, `Business Entity Controller`, `Eloquent Relationships`, `Entity Relationship Models`, `Bank Account Security`, `Core Entity Models`, `Persons Workspace Controller`, `Statement Upload Service`, `Account Display Attributes`, `Bank Reconciliation Testing`, `Asset and Lease Management`, `Workspace and Report Controllers`?**
  _High betweenness centrality (0.050) - this node is a cross-community bridge._
- **Why does `Transaction` connect `Transaction Model Logic` to `Bank Account Linking`, `Password Security Policy`, `Bank Transaction Importing`, `Task and Due Date Tracking`, `Document Storage System`, `Gmail and Invoice Sync`, `Document Upload Controller`, `Entity Person Management`, `Vendor Management`, `Financial Reporting Service`, `Business Entity Controller`, `Eloquent Relationships`, `Entity Relationship Models`, `Director Loan Reporting`, `Transaction Posting Service`, `Core Entity Models`, `Property Reporting Service`, `Bank Account Relations`, `Document Migration Services`, `Depreciation Posting Service`, `Document Upload Service`, `Bank Reconciliation Testing`, `Workspace and Report Controllers`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Are the 44 inferred relationships involving `BusinessEntity` (e.g. with `.handle()` and `.listHtmlForContext()`) actually correct?**
  _`BusinessEntity` has 44 INFERRED edges - model-reasoned connections that need verification._
- **Are the 24 inferred relationships involving `BankAccount` (e.g. with `.bankAccountPickerData()` and `.syncBankAccountLinks()`) actually correct?**
  _`BankAccount` has 24 INFERRED edges - model-reasoned connections that need verification._
- **Are the 26 inferred relationships involving `Asset` (e.g. with `.assetOperationalQuery()` and `.dashboard()`) actually correct?**
  _`Asset` has 26 INFERRED edges - model-reasoned connections that need verification._
- **What connects `py`, `npx`, `@upstash/context7-mcp` to the rest of the system?**
  _544 weakly-connected nodes found - possible documentation gaps or missing edges._