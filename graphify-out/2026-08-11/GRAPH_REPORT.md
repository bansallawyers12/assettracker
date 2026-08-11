# Graph Report - .  (2026-08-11)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 3960 nodes · 8672 edges · 550 communities (438 shown, 112 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 489 edges (avg confidence: 0.79)
- Token cost: 20,825 input · 6,376 output

## Graph Freshness
- Built from commit: `e00c30cc`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Business Entity Transactions
- Attribute Encryption Trait
- Compliance Task Tracking
- Compliance API Resources
- Email Templates
- Encrypted Database Backups
- Admin Workspace Scripts
- Bank Statement Import
- Core Domain Models
- Authentication and Sessions
- Document Storage Service
- Compliance Workspace UI
- Layout View Components
- Gmail and Invoicing
- Form Field Synchronization
- Frontend Dependencies
- Person Form Logic
- Tracking Categories
- Mail and Attachments
- Asset Depreciation Logic
- Data Encryption Helpers
- Contact List Management
- Filename Matching Logic
- Bank Account Modals
- Person Entity Management
- Compliance Year Service
- Compliance Date Logic
- Transaction Model Attributes
- Vendor Management
- User Model Logic
- Financial Reporting Service
- Document Management
- Asset Invoicing
- Compliance Workspace Management
- Development Guidelines
- Mail Message Allocation
- Compliance Document Uploads
- Bank Statement Management
- ATO Lodgement Reports
- Balance Sheet Entries
- Document Migration Service
- Compliance Document Types
- Database Migrations
- ASIC Renewal Testing
- Bank PDF Parsing
- System Documentation Index
- Asset Tenant Management
- Email Draft Relations
- Transaction Payment UI
- Reminder Management
- Report Scope Resolution
- Transaction Posting Service
- Core Application Controllers
- Rich Text Editor
- Asset Link Testing
- Two-Factor Authentication
- Form Request Validation
- Gmail Integration
- Lodgement Status Reporting
- Rent Collection Linking
- File Upload Validation
- Property Portfolio Reporting
- Composer and Scripts
- Console Commands
- Accounting Rule Docs
- Bank Account Management
- Pest Testing Documentation
- Account Masking Tests
- Compliance Reminder Sync
- Bank Account Privacy
- Entity Registration Testing
- Tailwind CSS Documentation
- Financial Report Hubs
- Address Formatting
- Security Middleware
- Business Entity Views
- PDF Text Extraction
- Form Submission UI
- Chart of Accounts
- Depreciation Posting
- Compliance Roadmap
- Document Upload Service
- Business Entity Bank Accounts
- Composer Configuration
- Production Dependencies
- Asset Workspace Controller
- Portfolio Account Formatting
- ATO Filing Reference
- Development Dependencies
- Feature Integration Tests
- Python Environment Setup
- Asset Workspace Management
- Transaction Split Logic
- Project Audit and Plans
- Laravel Framework Metadata
- Statement Row Parsing
- Statement Upload Service
- Bank Statement Model
- Chart of Accounts Documentation
- Document Category Migrations
- Code Review Checklist
- Convention Inference Guide
- Asset and Lease Management
- Security Audit Tool
- Security Setup Command
- Compliance Reporting Logic
- Asset Detail Partials
- Composer Plugin Config
- Compliance Category Setup
- Architecture Best Practices
- Lodgement Tracking Context
- Encrypted Backup Restoration
- Laravel Best Practices
- Email Service API
- Asset Form JS
- Encrypted User Provider
- Autoload Mapping
- Person Detail Partials
- User Profile Partials
- Event Service Provider
- Route Service Provider
- Navigation Layout Components
- Entity Encryption Migration
- Account Purpose Migration
- Email Hash Migration
- Mail Foreign Keys
- Asset Creation Views
- Asset Editing Views
- Transaction Creation Views
- Transaction Edit Views
- HTTP Kernel Middleware
- Application Service Providers
- Bank Account UI Components
- Security Best Practices
- Financial Report Controllers
- Report Scope Queries
- Statement PDF Normalization
- Admin Security Middleware
- Queue Best Practices
- Console Kernel Scheduling
- Chart of Accounts
- Asset Workspace UI
- Reminder Access Policies
- Business Entity Policies
- Entity Relationship Mapping
- Compliance Year Testing
- CRM Bug Tracking
- Frontend Refactoring Plan
- Commitment Access Policies
- UX Helper Roadmap
- Password Security Logic
- Deployment Documentation
- Database Seeders
- Table Sorting Tests
- Bank Statement Tests
- Icon Management Roadmap
- Financial Reports JS
- Dashboard Transaction Partials
- Global Navigation Search
- Person Management Views
- Bank Account Creation
- Bank Account Editing
- Entity Form Actions
- Portfolio Creation Forms
- Portfolio Editing Forms
- Entity Bank Creation
- Entity Bank Editing
- Entity Bank Forms
- Person Bank Forms
- Bank Account Forms
- Vendor Management Forms
- User Management List
- User Row Actions
- Lease Edit Form
- Tenant Edit Form
- Bank Account Picker
- Banking Detail Cells
- Portfolio Bank Accounts
- Business Entity Assets
- Contact List Workspace
- Entity Notes Workspace
- Entity Persons Workspace
- Email Template List
- Email Template Actions
- Account Transaction Details
- Financial Report Filters
- Portfolio Report Filters
- Financial Report Scope
- Python Dependency Script
- Email Service Script
- System Startup Script
- Asset Due Dates
- Asset Index View
- Asset Sidebar Details
- Bank Account Actions
- Loan Banking Fields
- Bank Account Configuration
- Grouped Bank Accounts
- Entity Creation Form
- Rent Collection Fields
- Entity Bank Accounts
- Rent Asset Configuration
- Person Account Links
- Person List Actions
- Profile Creation Fields
- Commitment Creation Form
- Commitment Edit Form
- ATO Compliance Warnings
- Missing ITR Reports
- Person Bank Accounts
- Bank Account Modal
- Vendor Creation Form
- System Feature Areas
- Advanced Query Optimization
- Invoices and Rent
- Commitments and Tracking
- Financial Property Reports
- Email and Templates
- Reminders and Contacts
- API and Policies
- Auth and Security
- Admin User Management
- Dashboard and Profile
- Business Entity Management
- Persons and Roles
- Asset Lifecycle Management
- Document Management
- Compliance Management
- Banking and Transactions
- MCP Configuration
- Encrypted File Storage
- Admin User Management
- Database Performance Guide
- Events and Notifications
- Rent Link Form
- Document Access Policies
- Bank Statement List
- Caching Best Practices
- Eloquent ORM Patterns
- Migration Best Practices
- Money Parsing Utilities
- Architecture Overview
- Import Matching Panel
- Transaction Reconciliation UI
- Transaction Entry Forms
- Statement Date Parsing
- Note Management
- Custom Validation Rules
- User Model Factory
- Blade View Patterns
- Error Handling Patterns
- Task Scheduling Patterns
- Testing Best Practices
- Rate Limiting Middleware
- Collection Best Practices
- HTTP Client Patterns
- Mail Best Practices
- Routing and Controllers
- Code Style Conventions
- Validation Best Practices
- PDF Parsing Service
- Transaction Text Grouping
- Email Parsing Utility
- Project Initialization Commands
- Laravel Package Configuration
- Transaction Panel View

## God Nodes (most connected - your core abstractions)
1. `BusinessEntity` - 489 edges
2. `BankAccount` - 197 edges
3. `Asset` - 156 edges
4. `Transaction` - 127 edges
5. `BusinessEntityController` - 114 edges
6. `User` - 98 edges
7. `Controller` - 72 edges
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

## Communities (550 total, 112 thin omitted)

### Community 1 - "Attribute Encryption Trait"
Cohesion: 0.38
Nodes (11): addEncryptedAttribute(), attributesToArray(), decrypt(), decryptAttributes(), encrypt(), encryptAttributes(), getAttribute(), getEncryptedAttributes() (+3 more)

### Community 2 - "Compliance Task Tracking"
Cohesion: 0.07
Nodes (7): BillsTasksController, Collection, CommitmentController, Commitment, CommitmentReportService, Carbon, Illuminate\Database\Eloquent\Builder

### Community 3 - "Compliance API Resources"
Cohesion: 0.12
Nodes (8): ComplianceCategoryResource, ComplianceDocumentFileResource, ComplianceDocumentTypeResource, ComplianceYearWorkspaceResource, ContactListResource, DocumentCategoryResource, DocumentSlotResource, Illuminate\Http\Resources\Json\JsonResource

### Community 6 - "Admin Workspace Scripts"
Cohesion: 0.15
Nodes (43): alertHttpError(), boot(), initAdminUsersWorkspace(), pageQuery(), withPageQuery(), workspaceUrl(), bindReconciliationPanel(), alertHttpError() (+35 more)

### Community 7 - "Bank Statement Import"
Cohesion: 0.07
Nodes (9): BankAccountImportController, BankImportController, BankStatementEntry, BankCsvStatementParser, BankStatementApplyService, BankStatementMatchSuggester, Carbon, BankStatementParseService (+1 more)

### Community 8 - "Core Domain Models"
Cohesion: 0.11
Nodes (8): CommitmentPayment, DepreciationSchedule, Email, InvoiceLine, AuthServiceProvider, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Foundation\Support\Providers\AuthServiceProvider

### Community 9 - "Authentication and Sessions"
Cohesion: 0.06
Nodes (22): AuthenticatedSessionController, ConfirmablePasswordController, EmailVerificationNotificationController, EmailVerificationPromptController, NewPasswordController, PasswordController, PasswordResetLinkController, TwoFactorController (+14 more)

### Community 10 - "Document Storage Service"
Cohesion: 0.14
Nodes (3): DocumentStorage, Illuminate\Contracts\Filesystem\Filesystem, static

### Community 11 - "Compliance Workspace UI"
Cohesion: 0.09
Nodes (46): alertHttpError(), alertValidationErrors(), api(), bindTabActivation(), bootWorkspaces(), buildCategoryPanel(), buildFileRow(), buildStatusCell() (+38 more)

### Community 12 - "Layout View Components"
Cohesion: 0.32
Nodes (3): AppLayout, GuestLayout, Illuminate\View\Component

### Community 13 - "Gmail and Invoicing"
Cohesion: 0.08
Nodes (15): GmailSyncCommand, InvoiceController, SyncGmailForUser, ContactEmail, InvoiceReminderMail, Invoice, InvoicePaymentService, Illuminate\Bus\Queueable (+7 more)

### Community 14 - "Form Field Synchronization"
Cohesion: 0.12
Nodes (33): initAddressFieldSync(), syncAddressFieldsInForm(), bootApp(), exposeRichTextHelpers(), loadRichTextModule(), initEntityCreateForm(), initEntityFormFields(), setCompanyFieldsState() (+25 more)

### Community 15 - "Frontend Dependencies"
Cohesion: 0.05
Nodes (41): alpinejs, concurrently, flatpickr, laravel-vite-plugin, dependencies, flatpickr, @tiptap/core, @tiptap/extension-color (+33 more)

### Community 16 - "Person Form Logic"
Cohesion: 0.13
Nodes (35): initPersonForm(), initPersonsToggleLogic(), isSelectInVisibleSection(), refreshPersonFormSelects(), scheduleRefreshPersonFormSelects(), activateTomSelectsIn(), addNativeOptionToTomSelect(), bindDropdownReposition() (+27 more)

### Community 17 - "Tracking Categories"
Cohesion: 0.12
Nodes (4): TrackingCategoryController, TrackingSubCategoryController, TrackingCategory, TrackingSubCategory

### Community 18 - "Mail and Attachments"
Cohesion: 0.16
Nodes (4): MailAttachment, MailMessage, MsgParserService, Illuminate\Database\Eloquent\Relations\BelongsToMany

### Community 19 - "Asset Depreciation Logic"
Cohesion: 0.08
Nodes (3): Asset, down(), up()

### Community 20 - "Data Encryption Helpers"
Cohesion: 0.20
Nodes (3): BackfillPersonsEncryption, ReencryptModelAttributes, EncryptionHelper

### Community 21 - "Contact List Management"
Cohesion: 0.20
Nodes (3): ContactListController, ContactList, ContactListPolicy

### Community 23 - "Bank Account Modals"
Cohesion: 0.10
Nodes (25): availablePurposes(), bindCollapsibleFilters(), bindTransactionsPanel(), buildCreateFormUrl(), getSelectedAccountOption(), getSelectValue(), initBankAccountModal(), initBankTransactionsPage() (+17 more)

### Community 24 - "Person Entity Management"
Cohesion: 0.05
Nodes (8): EntityPersonController, PersonShowWorkspaceController, PersonsIndexWorkspaceController, PersonsWorkspaceController, EntityPersonResource, EntityPerson, Person, TransactionPayerResolver

### Community 25 - "Compliance Year Service"
Cohesion: 0.20
Nodes (3): ComplianceYearRecord, ComplianceYearService, Carbon

### Community 26 - "Compliance Date Logic"
Cohesion: 0.18
Nodes (6): BackfillComplianceDueDates, Carbon, AtoDueDateService, Carbon, Carbon\Carbon, Carbon\CarbonInterface

### Community 27 - "Transaction Model Attributes"
Cohesion: 0.08
Nodes (3): Transaction, TransactionObserver, makeTransaction()

### Community 28 - "Vendor Management"
Cohesion: 0.16
Nodes (3): VendorController, Vendor, VendorSyncService

### Community 29 - "User Model Logic"
Cohesion: 0.07
Nodes (5): User, AssetPolicy, ComplianceDocumentFilePolicy, ComplianceYearRecordPolicy, Illuminate\Foundation\Auth\User

### Community 30 - "Financial Reporting Service"
Cohesion: 0.10
Nodes (3): FinancialReportService, Carbon, Illuminate\Database\Eloquent\Collection

### Community 31 - "Document Management"
Cohesion: 0.12
Nodes (4): DocumentController, DocumentWorkspaceController, Document, DocumentCategory

### Community 32 - "Asset Invoicing"
Cohesion: 0.15
Nodes (5): AssetInvoiceController, RentInvoiceController, Lease, Carbon, RentInvoiceService

### Community 34 - "Development Guidelines"
Cohesion: 0.07
Nodes (27): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+19 more)

### Community 36 - "Compliance Document Uploads"
Cohesion: 0.14
Nodes (3): ComplianceController, ComplianceDocumentFile, ComplianceUploadService

### Community 38 - "ATO Lodgement Reports"
Cohesion: 0.33
Nodes (3): ComplianceReportController, Carbon, Symfony\Component\HttpFoundation\StreamedResponse

### Community 39 - "Balance Sheet Entries"
Cohesion: 0.07
Nodes (4): BalanceSheetEntryController, resolveReportEntityIds(), TransactionGstResolver, Illuminate\Http\Request

### Community 41 - "Compliance Document Types"
Cohesion: 0.16
Nodes (4): ComplianceDocumentType, down(), up(), up()

### Community 44 - "Bank PDF Parsing"
Cohesion: 0.20
Nodes (21): ensure_westpac_row_width(), is_header_row(), is_westpac_continuation_row(), look_like_money(), merge_continuation_row(), merge_westpac_continuation(), merge_westpac_table_rows(), money_values_from_cells() (+13 more)

### Community 45 - "System Documentation Index"
Cohesion: 0.10
Nodes (20): Accounting, API, Asset Tracker, Bank statements, Communication, Core, Creating users, Database (key tables) (+12 more)

### Community 46 - "Asset Tenant Management"
Cohesion: 0.12
Nodes (4): RealEstateCompany, RealEstateCompanyContact, Tenant, up()

### Community 47 - "Email Draft Relations"
Cohesion: 0.06
Nodes (3): EmailDraft, TransactionLine, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 48 - "Transaction Payment UI"
Cohesion: 0.16
Nodes (20): bindBookingEntityChange(), bindPaidBySelectChange(), bindPaidByTomSelectChange(), bindPaymentStatusChange(), bookingEntityId(), initTransactionPaidByBankAccount(), parseEntityIdFromPaidBy(), paymentChannel() (+12 more)

### Community 50 - "Report Scope Resolution"
Cohesion: 0.23
Nodes (3): mergeReportFormScope(), ReportEntityScopeResolver, ReportEntityScopeResolverTest

### Community 51 - "Transaction Posting Service"
Cohesion: 0.16
Nodes (3): RepostPaidTransactionJournals, DateTimeInterface, TransactionPostingService

### Community 52 - "Core Application Controllers"
Cohesion: 0.07
Nodes (3): ensureNotClosed(), ensureOperationalForAccounting(), Illuminate\Database\Eloquent\Relations\Relation

### Community 53 - "Rich Text Editor"
Cohesion: 0.26
Nodes (16): buildToolbar(), cleanupEditorShell(), createDivider(), createEditorShell(), createToolbarButton(), destroyRichTextEditor(), escapeSelectorId(), fieldValue() (+8 more)

### Community 54 - "Asset Link Testing"
Cohesion: 0.07
Nodes (6): PHPUnit\Framework\TestCase, AssetDueDateRemindersTest, AssetLoanAccountLinkTest, BankAccountAccessTest, BankAccountAssetLinkServiceTest, TwoFactorServiceTest

### Community 56 - "Form Request Validation"
Cohesion: 0.27
Nodes (3): LoginRequest, ProfileUpdateRequest, Illuminate\Foundation\Http\FormRequest

### Community 57 - "Gmail Integration"
Cohesion: 0.26
Nodes (3): MailLabel, GmailFetcher, GuzzleHttp\Client

### Community 61 - "Property Portfolio Reporting"
Cohesion: 0.17
Nodes (3): Carbon, PropertyReportService, PropertyReportServiceTest

### Community 62 - "Composer and Scripts"
Cohesion: 0.18
Nodes (11): scripts, dev, post-autoload-dump, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" --names=server,queue,logs (+3 more)

### Community 63 - "Console Commands"
Cohesion: 0.21
Nodes (5): AddXeroChartOfAccounts, EnsureComplianceYears, ScheduleBackup, Illuminate\Console\Command, Illuminate\Support\Facades\Schedule

### Community 64 - "Accounting Rule Docs"
Cohesion: 0.25
Nodes (5): Balance sheet entries vs add transaction, Rule, Project rules index, Loan vs offset accounts, Rule

### Community 65 - "Bank Account Management"
Cohesion: 0.04
Nodes (5): BankAccountsWorkspaceController, BankAccountTransactionController, BankAccount, BusinessEntityBankAccount, BankAccountFormTest

### Community 66 - "Pest Testing Documentation"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 71 - "Tailwind CSS Documentation"
Cohesion: 0.14
Nodes (13): Basic Usage, Common Patterns, Common Pitfalls, CSS-First Configuration, Dark Mode, Documentation, Flexbox Layout, Grid Layout (+5 more)

### Community 74 - "Security Middleware"
Cohesion: 0.23
Nodes (6): EnsureAccountActive, EnsureTwoFactorEnrolled, SecurityHeaders, TwoFactorVerified, Closure, Symfony\Component\HttpFoundation\Response

### Community 75 - "Business Entity Views"
Cohesion: 0.15
Nodes (12): business-entities.partials.assets-workspace, business-entities.partials.bank-accounts.list, business-entities.partials.bank-import-summary, business-entities.partials.close-entity-modal, business-entities.partials.contact-lists-workspace, business-entities.partials.entity-details-sidebar, business-entities.partials.notes-workspace, business-entities.partials.persons-workspace (+4 more)

### Community 76 - "PDF Text Extraction"
Cohesion: 0.11
Nodes (9): cells_from_text_line(), disambiguate_amount_balance(), extract_leading_date_from_line(), glue_money_suffix(), infer_sign_from_description(), Attach detached CR/DR markers to their amount: '607.06 Dr' -> '607.06Dr'., Compact PDF text often collapses blank debit/credit cells into: Date |…, Westpac text often keeps date+desc in one cell when amounts use wide gaps. (+1 more)

### Community 77 - "Form Submission UI"
Cohesion: 0.30
Nodes (11): disableSubmitter(), ensureOverlay(), hideFormSaving(), initGlobalFormSaving(), isFormSaving(), isWorkspaceFormSaving(), lockFormFields(), restoreSubmitter() (+3 more)

### Community 78 - "Chart of Accounts"
Cohesion: 0.15
Nodes (3): ChartOfAccount, JournalLine, InvoicePostingService

### Community 79 - "Depreciation Posting"
Cohesion: 0.12
Nodes (5): PostMonthlyDepreciation, JournalEntry, DepreciationPostingService, DateTimeInterface, ManualJournalEntryService

### Community 80 - "Compliance Roadmap"
Cohesion: 0.14
Nodes (14): ATO Lodgement Tracking — Findings & Proposal, Current compliance config (`config/compliance.php`), Disclaimer, Executive Summary, Part 3 — Gaps, Part 5 — Implementation Plan, Part 6 — Configuration Reference, Part 7 — Recommendation (+6 more)

### Community 83 - "Composer Configuration"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 84 - "Production Dependencies"
Cohesion: 0.18
Nodes (11): require, bacon/bacon-qr-code, fakerphp/faker, laravel/framework, laravel/tinker, league/flysystem, league/flysystem-aws-s3-v3, php (+3 more)

### Community 86 - "Portfolio Account Formatting"
Cohesion: 0.05
Nodes (6): FinancialYear, Carbon, Exists, self, AtoDueDateServiceTest, FinancialYearTest

### Community 87 - "ATO Filing Reference"
Cohesion: 0.12
Nodes (17): Annual GST (voluntary registration, turnover under $75k / $150k NFP), Before 1 July 2026 (quarterly system), Companies, Fringe Benefits Tax (FBT), From 1 July 2026 — Payday Super, GST & BAS (Business Activity Statement), Income Tax Returns (30 June balance date), Individuals & sole traders (+9 more)

### Community 88 - "Development Dependencies"
Cohesion: 0.18
Nodes (11): require-dev, laravel/boost, laravel/breeze, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision (+3 more)

### Community 89 - "Feature Integration Tests"
Cohesion: 0.11
Nodes (4): CreatesApplication, Illuminate\Foundation\Testing\TestCase, TestCase, FinancialReportsHubPageTest

### Community 90 - "Python Environment Setup"
Cohesion: 0.29
Nodes (9): check_python(), install_dependencies(), main(), Ensure Python 3 is available., Install requirements.txt., Verify all required packages can be imported., Verify parser scripts exist., verify_imports() (+1 more)

### Community 93 - "Project Audit and Plans"
Cohesion: 0.14
Nodes (14): Comparison with Migration Manager CRM, Current stack (as of audit), Done, Executive summary, Out of scope (this plan), Plan, PR sizing guide, Pre-deploy checklist (each phase) (+6 more)

### Community 94 - "Laravel Framework Metadata"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 95 - "Statement Row Parsing"
Cohesion: 0.24
Nodes (8): build_entry(), parse_row_cells(), Build one statement row using fixed columns: date | description | amount_debit…, Remove trailing amount/balance tokens from a description (Macquarie compact…, should_skip_description(), strip_trailing_money(), FixedColumnsAllBanksTest, Balance moving 3,368.35DR -> 3,517.35DR is a debit, not a credit.

### Community 99 - "Document Category Migrations"
Cohesion: 0.25
Nodes (6): backfillCategoriesAndLabels(), up(), backfillNullLabels(), deduplicateLabels(), reassignOrphans(), up()

### Community 100 - "Code Review Checklist"
Cohesion: 0.17
Nodes (11): A. Validation & HTTP input, B. Controllers & routing, C. Authorization, D. Eloquent & models, Detection Checklist, E. Architecture & organization, F. Frontend & views, G. Database & migrations (+3 more)

### Community 101 - "Convention Inference Guide"
Cohesion: 0.17
Nodes (11): Edge cases, Glob mapping, Ground Rules (read before you start), Infer Conventions, Process, Step 0: Orient, Step 1: Predefined sweep, Step 2: Open-ended pass (+3 more)

### Community 106 - "Compliance Reporting Logic"
Cohesion: 0.20
Nodes (10): Filters, Obligation types in scope (Phase 1), Part 4 — Proposed Report: ATO / ASIC Lodgement Status, Past-year gap detection logic, Purpose, Report columns, Status classification (locked for Phase 1), Suggested route (+2 more)

### Community 108 - "Asset Detail Partials"
Cohesion: 0.29
Nodes (6): assets.partials.asset-details-sidebar, assets.partials.invoices-tab, assets.partials.linked-bank-accounts-show, assets.partials.loan-banking-show, business-entities.partials.compliance-workspace, business-entities.partials.documents-workspace

### Community 109 - "Composer Plugin Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 110 - "Compliance Category Setup"
Cohesion: 0.48
Nodes (6): addLabelUniqueIndex(), backfillCategoriesAndFiles(), down(), dropLabelUniqueIndex(), seedCategoryGroups(), up()

### Community 111 - "Architecture Best Practices"
Cohesion: 0.17
Nodes (11): Architecture Best Practices, Code to Interfaces, Convention Over Configuration, Default Sort by Descending, Single-Purpose Action Classes, Use Atomic Locks for Race Conditions, Use `Concurrency::run()` for Parallel Execution, Use `Context` for Request-Scoped Data (+3 more)

### Community 112 - "Lodgement Tracking Context"
Cohesion: 0.22
Nodes (9): Database tables supporting lodgement tracking, Existing compliance features, Existing reporting, How due dates work today, Part 2 — Current State in Asset Tracker, Project context, Provisioning behaviour (important for past-year reports), Report generation pattern (to reuse) (+1 more)

### Community 114 - "Laravel Best Practices"
Cohesion: 0.17
Nodes (10): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets, Consistency First, Decision Rules, How to Apply (+2 more)

### Community 115 - "Email Service API"
Cohesion: 0.40
Nodes (5): get, post, health(), parse_email(), UploadFile

### Community 116 - "Asset Form JS"
Cohesion: 0.60
Nodes (5): bootAssetCreateForms(), initAssetCreateForm(), initAssetTypeToggle(), initCollapsibleSections(), scrollToFirstError()

### Community 117 - "Encrypted User Provider"
Cohesion: 0.60
Nodes (3): EncryptedEmailUserProvider, Illuminate\Auth\EloquentUserProvider, Illuminate\Contracts\Auth\Authenticatable

### Community 118 - "Autoload Mapping"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 119 - "Person Detail Partials"
Cohesion: 0.40
Nodes (4): persons.partials.bank-accounts.list, persons.partials.person-bank-account-modal, persons.partials.roles-list, persons.partials.summary-stats

### Community 120 - "User Profile Partials"
Cohesion: 0.40
Nodes (4): profile.partials.delete-user-form, profile.partials.two-factor-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 123 - "Navigation Layout Components"
Cohesion: 0.50
Nodes (3): bank-accounts.partials.bank-account-panel-shell, business-entities.partials.entity-workspace-panel, layouts.navigation

### Community 125 - "Account Purpose Migration"
Cohesion: 0.83
Nodes (3): alterAccountPurposeConstraint(), down(), up()

### Community 126 - "Email Hash Migration"
Cohesion: 0.83
Nodes (3): down(), indexExists(), up()

### Community 127 - "Mail Foreign Keys"
Cohesion: 0.83
Nodes (3): down(), hasUserForeignKey(), up()

### Community 128 - "Asset Creation Views"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 129 - "Asset Editing Views"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 130 - "Transaction Creation Views"
Cohesion: 0.40
Nodes (4): partials.transaction-marker-fields, partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 131 - "Transaction Edit Views"
Cohesion: 0.40
Nodes (4): partials.transaction-marker-fields, partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 133 - "Application Service Providers"
Cohesion: 0.10
Nodes (7): AppServiceProvider, HeaderSearchIndex, PasswordPolicy, ReportEntityScopeLabel, Illuminate\Pagination\LengthAwarePaginator, Illuminate\Validation\Rules\Password, ReportEntityScopeLabelTest

### Community 135 - "Security Best Practices"
Cohesion: 0.17
Nodes (11): Audit Dependencies, Authorize Every Action, CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Keep Secrets Out of Code, Mass Assignment Protection, Prevent SQL Injection (+3 more)

### Community 136 - "Financial Report Controllers"
Cohesion: 0.15
Nodes (6): CarReportController, PropertyReportController, AssetSummaryReportService, CarReportService, Carbon, Illuminate\Support\Collection

### Community 159 - "Admin Security Middleware"
Cohesion: 0.25
Nodes (3): EnsureSuperAdmin, Response, Illuminate\Notifications\Notifiable

### Community 160 - "Queue Best Practices"
Cohesion: 0.18
Nodes (10): Always Implement `failed()`, Batch Related Jobs, Implement `ShouldBeUnique`, Queue & Job Best Practices, Rate Limit External API Calls in Jobs, `retryUntil()` Needs `$tries = 0`, Set `retry_after` Greater Than `timeout`, Use Exponential Backoff (+2 more)

### Community 163 - "Console Kernel Scheduling"
Cohesion: 0.29
Nodes (4): Kernel, Command, Illuminate\Console\Scheduling\Schedule, Illuminate\Foundation\Console\Kernel

### Community 165 - "Asset Workspace UI"
Cohesion: 0.20
Nodes (14): alertHttpError(), boot(), ensurePanelFormHandlers(), initAssetShowWorkspace(), initFormPlugins(), panelFormHandlers, registerPanelFormHandler(), dismissToast() (+6 more)

### Community 186 - "CRM Bug Tracking"
Cohesion: 0.25
Nodes (7): Area 10 — Chart of accounts & vendors, CRM / Asset Tracker — Potential Bugs, Low, Medium, Notes, Re-verification changelog, Suggested fix priority (cross-area) — open items only

### Community 192 - "Frontend Refactoring Plan"
Cohesion: 0.25
Nodes (8): Current pain points, Phase 1a — Split workspace bundles, Phase 1b — Dashboard entry, Phase 1c — Entity & asset show entries, Phase 1d — Remaining inline scripts (incremental), Phase 1e — Optional lazy Tiptap tightening, Plan, Track 1: Vite page splitting & script extraction

### Community 212 - "UX Helper Roadmap"
Cohesion: 0.29
Nodes (7): Current state, Phase 2a — Flash component, Phase 2b — Toast helper (JS), Phase 2c — Confirm helper (JS + Blade), Phase 2d — Form confirm pattern (Blade), Plan, Track 2: UX helpers (flash, toast, confirm)

### Community 214 - "Deployment Documentation"
Cohesion: 0.22
Nodes (6): Deploy, Frontend build (Tailwind / Vite), Host, Post-deploy (required), Production server, Runtime versions (verified on server)

### Community 215 - "Database Seeders"
Cohesion: 0.25
Nodes (4): ChartOfAccountSeeder, ComplianceDocumentTypeSeeder, DatabaseSeeder, Illuminate\Database\Seeder

### Community 220 - "Icon Management Roadmap"
Cohesion: 0.33
Nodes (6): Current state, Phase 3a — CI / npm wiring, Phase 3b — JS dynamic icons (when needed), Phase 3c — Ongoing, Plan, Track 3: Icons — guardrails & dynamic JS

### Community 224 - "Financial Reports JS"
Cohesion: 0.43
Nodes (3): buildReportNavigationUrl(), initFinancialReportsHub(), navigateToReport()

### Community 232 - "Dashboard Transaction Partials"
Cohesion: 0.50
Nodes (3): partials.dashboard-transaction-lines, partials.transaction-marker-fields, partials.transaction-paid-by-fields

### Community 457 - "System Feature Areas"
Cohesion: 0.33
Nodes (6): Accounting, Asset management, Business entities, Documents & communication, Features, Security & access

### Community 458 - "Advanced Query Optimization"
Cohesion: 0.20
Nodes (9): Advanced Query Patterns, Create Dynamic Relationships via Subquery FK, Prefer `whereIn` + Subquery Over `whereHas`, Sometimes Two Simple Queries Beat One Complex Query, Use `addSelect()` Subqueries for Single Values from Has-Many, Use Compound Indexes Matching `orderBy` Column Order, Use Conditional Aggregates Instead of Multiple Count Queries, Use Correlated Subqueries for Has-Many Ordering (+1 more)

### Community 459 - "Invoices and Rent"
Cohesion: 0.50
Nodes (4): Area 11 — Invoices & rent, High, Low, Medium

### Community 460 - "Commitments and Tracking"
Cohesion: 0.50
Nodes (4): Area 12 — Commitments & tracking, High, Low, Medium

### Community 461 - "Financial Property Reports"
Cohesion: 0.50
Nodes (4): Area 13 — Financial & property reports, High, Low, Medium

### Community 462 - "Email and Templates"
Cohesion: 0.50
Nodes (4): Area 14 — Email & templates, High, Low, Medium

### Community 463 - "Reminders and Contacts"
Cohesion: 0.50
Nodes (4): Area 15 — Reminders / contact lists, High, Low, Medium

### Community 464 - "API and Policies"
Cohesion: 0.50
Nodes (4): Area 16 — Cross-cutting / API / policies, High, Low, Medium

### Community 465 - "Auth and Security"
Cohesion: 0.50
Nodes (4): Area 1 — Auth & security, High, Low, Medium

### Community 466 - "Admin User Management"
Cohesion: 0.50
Nodes (4): Area 2 — Admin user management, High, Low, Medium

### Community 467 - "Dashboard and Profile"
Cohesion: 0.50
Nodes (4): Area 3 — Dashboard & profile, High, Low, Medium

### Community 468 - "Business Entity Management"
Cohesion: 0.50
Nodes (4): Area 4 — Business entities, High, Low, Medium

### Community 469 - "Persons and Roles"
Cohesion: 0.50
Nodes (4): Area 5 — Persons & roles, High, Low, Medium

### Community 470 - "Asset Lifecycle Management"
Cohesion: 0.50
Nodes (4): Area 6 — Assets CRUD & lifecycle, High, Low, Medium

### Community 471 - "Document Management"
Cohesion: 0.50
Nodes (4): Area 7 — Documents, High, Low, Medium

### Community 472 - "Compliance Management"
Cohesion: 0.50
Nodes (4): Area 8 — Compliance, High, Low, Medium

### Community 473 - "Banking and Transactions"
Cohesion: 0.50
Nodes (4): Area 9 — Banking & transactions, High, Low, Medium

### Community 474 - "MCP Configuration"
Cohesion: 0.22
Nodes (8): context7, graphify, laravel-boost, CONTEXT7_API_KEY, npx, php, py, @upstash/context7-mcp

### Community 475 - "Encrypted File Storage"
Cohesion: 0.17
Nodes (5): EncryptedFilesystemAdapter, EncryptedFilesystemServiceProvider, Illuminate\Filesystem\FilesystemAdapter, Illuminate\Support\ServiceProvider, League\Flysystem\Filesystem

### Community 476 - "Admin User Management"
Cohesion: 0.06
Nodes (8): AdminUsersWorkspaceController, UserManagementController, ContactListsWorkspaceController, EmailTemplateController, EmailTemplatesWorkspaceController, EntityShowWorkspaceController, TableSort, Illuminate\Http\JsonResponse

### Community 477 - "Database Performance Guide"
Cohesion: 0.20
Nodes (9): Add Database Indexes, Always Eager Load Relationships, Chunk Large Datasets, Database Performance Best Practices, No Queries in Blade Templates, Prevent Lazy Loading in Development, Select Only Needed Columns, Use `cursor()` for Memory-Efficient Iteration (+1 more)

### Community 480 - "Events and Notifications"
Cohesion: 0.20
Nodes (9): Always Queue Notifications, Events & Notifications Best Practices, Implement `HasLocalePreference` on Notifiable Models, Rely on Event Discovery, Route Notification Channels to Dedicated Queues, Run `event:cache` in Production Deploy, Use `afterCommit()` on Notifications in Transactions, Use On-Demand Notifications for Non-User Recipients (+1 more)

### Community 496 - "Caching Best Practices"
Cohesion: 0.22
Nodes (8): Caching Best Practices, Configure Failover Cache Stores in Production, Use `Cache::add()` for Atomic Conditional Writes, Use `Cache::flexible()` for Stale-While-Revalidate, Use `Cache::memo()` to Avoid Redundant Hits Within a Request, Use `Cache::remember()` Instead of Manual Get/Put, Use Cache Tags to Invalidate Related Groups, Use `once()` for Per-Request Memoization

### Community 497 - "Eloquent ORM Patterns"
Cohesion: 0.22
Nodes (8): Apply Global Scopes Sparingly, Avoid Hardcoded Table Names in Queries, Cast Date Columns Properly, Define Attribute Casts, Eloquent Best Practices, Use Correct Relationship Types, Use Local Scopes for Reusable Queries, Use `whereBelongsTo()` for Relationship Queries

### Community 498 - "Migration Best Practices"
Cohesion: 0.22
Nodes (8): Add Indexes in the Migration, Generate Migrations with Artisan, Keep Migrations Focused, Migration Best Practices, Mirror Defaults in Model `$attributes`, Never Modify Deployed Migrations, Use `constrained()` for Foreign Keys, Write Reversible `down()` Methods by Default

### Community 499 - "Money Parsing Utilities"
Cohesion: 0.26
Nodes (11): Any, Decimal, balance_or_none(), money_or_none(), money_values_from_text(), parse_amount(), parse_text_block(), parse_westpac_text_block() (+3 more)

### Community 501 - "Architecture Overview"
Cohesion: 0.50
Nodes (4): Architecture, Key models, Notable services, Stack

### Community 506 - "Transaction Entry Forms"
Cohesion: 0.50
Nodes (3): partials.transaction-marker-fields, partials.transaction-paid-by-fields, partials.vendor-select

### Community 507 - "Statement Date Parsing"
Cohesion: 0.24
Nodes (6): adjust_year(), extract_year_hint_from_text(), is_plausible_statement_year(), parse_generic_text_block(), Pull a year from statement-period headers (avoid FY / amount false positives)., StatementDateParsingTest

### Community 509 - "Custom Validation Rules"
Cohesion: 0.15
Nodes (5): UniqueAbnHash, UniqueAcnHash, UniqueChecklistLabelInCategory, UniqueComplianceLabelInCategory, Illuminate\Contracts\Validation\ValidationRule

### Community 512 - "Blade View Patterns"
Cohesion: 0.25
Nodes (7): Blade & Views Best Practices, Prefer Blade Components Over `@include`, Use `$attributes->merge()` in Component Templates, Use `@aware` for Deeply Nested Component Props, Use Blade Fragments for Partial Re-Renders (htmx/Turbo), Use `@pushOnce` for Per-Component Scripts, Use View Composers for Shared View Data

### Community 513 - "Error Handling Patterns"
Cohesion: 0.25
Nodes (7): Add Context to Exception Classes, Enable `dontReportDuplicates()`, Error Handling Best Practices, Exception Reporting and Rendering, Force JSON Error Rendering for API Routes, Throttle High-Volume Exceptions, Use `ShouldntReport` for Exceptions That Should Never Log

### Community 515 - "Task Scheduling Patterns"
Cohesion: 0.25
Nodes (7): Task Scheduling Best Practices, Use `environments()` to Restrict Tasks, Use `onOneServer()` on Multi-Server Deployments, Use `runInBackground()` for Concurrent Long Tasks, Use Schedule Groups for Shared Configuration, Use `takeUntilTimeout()` for Time-Bounded Processing, Use `withoutOverlapping()` on Variable-Duration Tasks

### Community 516 - "Testing Best Practices"
Cohesion: 0.25
Nodes (7): Call `Event::fake()` After Factory Setup, Testing Best Practices, Use `Exceptions::fake()` to Assert Exception Reporting, Use Factory States and Sequences, Use `LazilyRefreshDatabase` Over `RefreshDatabase`, Use Model Assertions Over Raw Database Assertions, Use `recycle()` to Share Relationship Instances Across Factories

### Community 519 - "Collection Best Practices"
Cohesion: 0.29
Nodes (6): Choose `cursor()` vs. `lazy()` Correctly, Collection Best Practices, Use `#[CollectedBy]` for Custom Collection Classes, Use Higher-Order Messages for Simple Operations, Use `lazyById()` When Updating Records While Iterating, Use `toQuery()` for Bulk Operations on Collections

### Community 520 - "HTTP Client Patterns"
Cohesion: 0.29
Nodes (6): Always Set Explicit Timeouts, Fake HTTP Calls in Tests, Handle Errors Explicitly, HTTP Client Best Practices, Use Request Pooling for Concurrent Requests, Use Retry with Backoff for External APIs

### Community 521 - "Mail Best Practices"
Cohesion: 0.29
Nodes (6): Implement `ShouldQueue` on the Mailable Class, Mail Best Practices, Separate Content Tests from Sending Tests, Use `afterCommit()` on Mailables Inside Transactions, Use `assertQueued()` Not `assertSent()` for Queued Mailables, Use Markdown Mailables for Transactional Emails

### Community 522 - "Routing and Controllers"
Cohesion: 0.29
Nodes (6): Keep Controllers Thin, Routing & Controllers Best Practices, Type-Hint Form Requests, Use Implicit Route Model Binding, Use Resource Controllers, Use Scoped Bindings for Nested Resources

### Community 523 - "Code Style Conventions"
Cohesion: 0.29
Nodes (6): Conventions & Style, Follow Laravel Naming Conventions, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers

### Community 524 - "Validation Best Practices"
Cohesion: 0.29
Nodes (6): Always Use `validated()`, Array vs. String Notation for Rules, Use Form Request Classes, Use `Rule::when()` for Conditional Validation, Use the `after()` Method for Custom Validation, Validation & Forms Best Practices

### Community 535 - "PDF Parsing Service"
Cohesion: 0.31
Nodes (9): Path, decrypt_pdf_if_needed(), detect_bank(), emit(), extract_entries(), main(), Return (path, error). If the PDF is encrypted with a non-empty password, return…, Always emit JSON on stdout so Laravel can decode failures reliably. (+1 more)

### Community 536 - "Transaction Text Grouping"
Cohesion: 0.33
Nodes (8): group_transaction_text_blocks(), group_westpac_text_blocks(), is_continuation_text_line(), is_month_year_header(), is_westpac_continuation_text(), line_starts_with_date(), True when a non-dated line is a Westpac wrap fragment, not footer/boilerplate., True when a non-dated line continues the previous transaction row.

### Community 539 - "Email Parsing Utility"
Cohesion: 0.36
Nodes (7): format_date(), main(), parse_recipients(), parse_sender(), Extract name and email from sender (string or object)., Extract recipients as list of strings., Format datetime to ISO string.

### Community 544 - "Project Initialization Commands"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 545 - "Laravel Package Configuration"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **551 isolated node(s):** `py`, `npx`, `@upstash/context7-mcp`, `CONTEXT7_API_KEY`, `php` (+546 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **112 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `BusinessEntity` connect `Business Entity Transactions` to `Attribute Encryption Trait`, `Compliance Task Tracking`, `Compliance API Resources`, `Application Service Providers`, `Bank Statement Import`, `Financial Report Controllers`, `Authentication and Sessions`, `Core Domain Models`, `Gmail and Invoicing`, `Tracking Categories`, `Mail and Attachments`, `Asset Depreciation Logic`, `Contact List Management`, `Person Entity Management`, `Compliance Year Service`, `Compliance Date Logic`, `Financial Reporting Service`, `Document Management`, `Asset Invoicing`, `Compliance Workspace Management`, `Mail Message Allocation`, `Chart of Accounts`, `Compliance Document Uploads`, `ATO Lodgement Reports`, `Balance Sheet Entries`, `Document Migration Service`, `Compliance Document Types`, `ASIC Renewal Testing`, `Asset Tenant Management`, `Business Entity Policies`, `Reminder Management`, `Core Application Controllers`, `Asset Link Testing`, `Lodgement Status Reporting`, `Rent Collection Linking`, `Console Commands`, `Bank Account Management`, `Bank Account Privacy`, `Entity Registration Testing`, `Financial Report Hubs`, `Address Formatting`, `Chart of Accounts`, `Depreciation Posting`, `Document Upload Service`, `Business Entity Bank Accounts`, `Asset Workspace Controller`, `Portfolio Account Formatting`, `Asset Workspace Management`, `Admin User Management`, `Document Access Policies`, `Asset and Lease Management`, `Note Management`?**
  _High betweenness centrality (0.124) - this node is a cross-community bridge._
- **Why does `BankAccount` connect `Bank Account Management` to `Business Entity Transactions`, `Attribute Encryption Trait`, `Compliance Task Tracking`, `Application Service Providers`, `Bank Statement Import`, `Core Domain Models`, `Financial Report Controllers`, `Document Storage Service`, `Gmail and Invoicing`, `Asset Depreciation Logic`, `Person Entity Management`, `Bank Statement Management`, `Balance Sheet Entries`, `Email Draft Relations`, `Core Application Controllers`, `Entity Relationship Mapping`, `Asset Link Testing`, `Rent Collection Linking`, `Account Masking Tests`, `Bank Account Privacy`, `Business Entity Bank Accounts`, `Portfolio Account Formatting`, `Statement Upload Service`, `Bank Statement Model`, `Asset and Lease Management`?**
  _High betweenness centrality (0.056) - this node is a cross-community bridge._
- **Why does `Asset` connect `Asset Depreciation Logic` to `Business Entity Transactions`, `Compliance Task Tracking`, `Compliance API Resources`, `Application Service Providers`, `Financial Report Controllers`, `Core Domain Models`, `Mail and Attachments`, `Compliance Year Service`, `User Model Logic`, `Document Management`, `Asset Invoicing`, `Mail Message Allocation`, `Compliance Document Uploads`, `Balance Sheet Entries`, `Document Migration Service`, `Asset Tenant Management`, `Reminder Management`, `Core Application Controllers`, `Asset Link Testing`, `Rent Collection Linking`, `Property Portfolio Reporting`, `Depreciation Posting`, `Document Upload Service`, `Asset Workspace Controller`, `Asset Workspace Management`, `Admin User Management`, `Asset and Lease Management`, `Note Management`?**
  _High betweenness centrality (0.043) - this node is a cross-community bridge._
- **Are the 44 inferred relationships involving `BusinessEntity` (e.g. with `.handle()` and `.listHtmlForContext()`) actually correct?**
  _`BusinessEntity` has 44 INFERRED edges - model-reasoned connections that need verification._
- **Are the 25 inferred relationships involving `BankAccount` (e.g. with `.bankAccountPickerData()` and `.syncBankAccountLinks()`) actually correct?**
  _`BankAccount` has 25 INFERRED edges - model-reasoned connections that need verification._
- **Are the 27 inferred relationships involving `Asset` (e.g. with `.store()` and `.assetOperationalQuery()`) actually correct?**
  _`Asset` has 27 INFERRED edges - model-reasoned connections that need verification._
- **Are the 19 inferred relationships involving `Transaction` (e.g. with `.handle()` and `.create()`) actually correct?**
  _`Transaction` has 19 INFERRED edges - model-reasoned connections that need verification._