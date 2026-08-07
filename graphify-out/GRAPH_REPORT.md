# Graph Report - .  (2026-08-07)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 3122 nodes · 7176 edges · 455 communities (348 shown, 107 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 467 edges (avg confidence: 0.78)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `403ce8ab`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- BusinessEntity
- Illuminate\Http\Request
- BankAccount
- MailMessageController
- ComplianceDocumentFile
- CreateEncryptedBackup
- workspace-panel.js
- Illuminate\Database\Eloquent\Model
- Reminder
- Illuminate\Http\RedirectResponse
- FinancialYear
- showWorkspaceAlert
- web.php
- FinancialReportService
- app.js
- dependencies
- tomselect-init.js
- EnsuresOperationalBusinessEntity.php
- Asset
- AssetController
- Illuminate\Console\Command
- Illuminate\Http\JsonResponse
- PHPUnit\Framework\TestCase
- bank-account-modal.js
- EntityPerson
- ComplianceYearRecord
- Illuminate\Database\Eloquent\Relations\HasMany
- Transaction
- Vendor
- User
- Person
- Note
- Lease
- Commitment
- DocumentStorage
- ContactList
- LoginRequest
- DocumentCategory
- Illuminate\View\View
- .rule
- Closure
- TransactionPostingService
- Illuminate\Database\Migrations\Migration
- BusinessEntityAsicRenewalDueDateTest
- TableSort
- Illuminate\Support\Collection
- Invoice
- ChartOfAccount
- transaction-paid-by-bank-account.js
- JournalLine
- BankAccountAssetLinkService
- Document
- Illuminate\Http\Resources\Json\JsonResource
- tiptap-init.js
- MailMessage
- FinancialReportController
- PersonsWorkspaceController
- Illuminate\Contracts\Validation\ValidationRule
- ComplianceReportService
- DocumentUploadService
- EmailTemplate
- PropertyReportService
- scripts
- Carbon\Carbon
- BankStatementEntry
- ReportEntityScopeResolver
- AssetsWorkspaceController
- TwoFactorService
- .handle
- DocumentUploadValidation
- BusinessEntityRegistrationDateTest
- ComplianceReportController
- DocumentController.php
- AustralianAddress
- EncryptsAttributes.php
- business-entities/show.blade.php
- python_bank_parser.py
- form-saving-ui.js
- Illuminate\Support\ServiceProvider
- ChartOfAccountController
- JournalEntry
- TransactionCashParts
- composer.json
- require
- Illuminate\Database\Seeder
- ComplianceYearServiceTest
- BusinessEntityBankAccount
- require-dev
- TestCase
- main
- TransactionPayerResolver
- CommitmentReportService
- .asicAnniversaryInYear
- EncryptedFilesystemAdapter
- .handle
- ReminderPolicy
- ReportEntityScopeLabel
- parse_msg_simple.py
- TableSortTest
- ChecklistFilenameMatcher
- AssetPolicy
- BusinessEntityPolicy
- CommitmentPolicy
- ContactListPolicy
- DocumentUploadService.php
- ReportScopeQuery
- assets/show.blade.php
- config
- 2026_06_22_000001_add_compliance_categories.php
- financial-reports-hub.js
- showToast
- PropertyReportServiceTest
- .canAccessEntity
- parse_email
- asset-create-form.js
- EncryptedEmailUserProvider.php
- psr-4
- persons/show.blade.php
- profile/edit.blade.php
- EventServiceProvider
- RouteServiceProvider
- app.blade.php
- 2026_06_16_092359_encrypt_business_entity_sensitive_fields.php
- 2026_06_29_100001_add_loan_repayment_paying_purpose_to_bank_accounts.php
- 2026_07_26_163700_add_unique_index_to_users_email_hash.php
- 2026_07_26_170000_null_on_delete_mail_user_fks.php
- assets/create.blade.php
- assets/edit.blade.php
- transactions/create.blade.php
- transactions/edit.blade.php
- Kernel
- AuthServiceProvider
- holder-grouped-list.blade.php
- extra
- keywords
- dashboard.blade.php
- navigation.blade.php
- persons/index.blade.php
- views/bank-accounts/create.blade.php
- views/bank-accounts/edit.blade.php
- entity/edit-form.blade.php
- portfolio/create-form.blade.php
- portfolio/edit-form.blade.php
- business-entities/bank-accounts/create.blade.php
- business-entities/bank-accounts/edit.blade.php
- business-entities/partials/bank-accounts/create-form.blade.php
- persons/partials/bank-accounts/create-form.blade.php
- bank-accounts/edit-form.blade.php
- vendors/edit.blade.php
- admin.users.partials.list
- admin.users.partials.row-actions
- assets.partials.leases.form
- assets.partials.tenants.form
- bank-accounts.partials.account-picker-row
- bank-accounts.partials.bsb-toggle-cell
- bank-accounts.partials.portfolio.list
- business-entities.partials.assets.list
- business-entities.partials.contact-lists.list
- business-entities.partials.notes.list
- business-entities.partials.persons.list
- email-templates.partials.list
- email-templates.partials.row-actions
- financial-reports.partials.account-transaction-line-details
- financial-reports.partials.report-scope-fields
- property-reports.partials.portfolio-filters
- property-reports.partials.report-filters
- install_python_deps.sh
- start_email_service.sh
- start.sh script
- index-all.blade.php
- assets/index.blade.php
- asset-details-sidebar.blade.php
- linked-bank-accounts-show.blade.php
- loan-banking-form.blade.php
- add-account-modal.blade.php
- portfolio/list.blade.php
- business-entities/create.blade.php
- attach-form.blade.php
- business-entities/partials/bank-accounts/list.blade.php
- rent-assets-form.blade.php
- persons/detail.blade.php
- persons/list.blade.php
- profile/form.blade.php
- commitments/create.blade.php
- commitments/edit.blade.php
- ato-lodgements.blade.php
- missing-itr.blade.php
- persons/partials/bank-accounts/list.blade.php
- person-bank-account-modal.blade.php
- vendors/create.blade.php

## God Nodes (most connected - your core abstractions)
1. `BusinessEntity` - 452 edges
2. `Asset` - 153 edges
3. `BankAccount` - 141 edges
4. `BusinessEntityController` - 108 edges
5. `User` - 98 edges
6. `Transaction` - 94 edges
7. `Controller` - 65 edges
8. `FinancialReportService` - 62 edges
9. `ComplianceDocumentFile` - 53 edges
10. `FinancialYear` - 52 edges

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

## Communities (455 total, 107 thin omitted)

### Community 0 - "BusinessEntity"
Cohesion: 0.04
Nodes (3): RentInvoiceController, BusinessEntity, Exists

### Community 1 - "Illuminate\Http\Request"
Cohesion: 0.08
Nodes (4): BusinessEntityController, Collection, SecurityAuditLogger, Illuminate\Http\Request

### Community 2 - "BankAccount"
Cohesion: 0.04
Nodes (3): BankAccount, BankAccountFormTest, BankAccountMaskingTest

### Community 3 - "MailMessageController"
Cohesion: 0.07
Nodes (17): GmailSyncCommand, MailMessageController, Closure, SyncGmailForUser, ContactEmail, InvoiceReminderMail, MailLabel, GmailFetcher (+9 more)

### Community 4 - "ComplianceDocumentFile"
Cohesion: 0.08
Nodes (8): ComplianceController, ComplianceWorkspaceController, ComplianceCategory, ComplianceDocumentFile, ComplianceFilenameMatcher, ComplianceUploadService, down(), up()

### Community 5 - "CreateEncryptedBackup"
Cohesion: 0.06
Nodes (10): CreateEncryptedBackup, RestoreEncryptedBackup, SecurityAudit, SetupSecurity, Kernel, BankImportController, Command, Illuminate\Console\Scheduling\Schedule (+2 more)

### Community 6 - "workspace-panel.js"
Cohesion: 0.14
Nodes (46): alertHttpError(), boot(), initAdminUsersWorkspace(), pageQuery(), withPageQuery(), workspaceUrl(), boot(), ensurePanelFormHandlers() (+38 more)

### Community 7 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.06
Nodes (8): DepreciationSchedule, Email, EmailDraft, MailAttachment, TransactionLine, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 8 - "Reminder"
Cohesion: 0.07
Nodes (5): BillsTasksController, Collection, ReminderController, Reminder, Illuminate\Database\Eloquent\Builder

### Community 9 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.08
Nodes (17): ConfirmablePasswordController, EmailVerificationNotificationController, EmailVerificationPromptController, NewPasswordController, PasswordController, PasswordResetLinkController, TwoFactorController, VerifyEmailController (+9 more)

### Community 10 - "FinancialYear"
Cohesion: 0.06
Nodes (5): FinancialYear, Carbon, self, AtoDueDateServiceTest, FinancialYearTest

### Community 11 - "showWorkspaceAlert"
Cohesion: 0.10
Nodes (44): alertHttpError(), alertHttpError(), alertValidationErrors(), api(), bindTabActivation(), bootWorkspaces(), buildCategoryPanel(), buildFileRow() (+36 more)

### Community 13 - "FinancialReportService"
Cohesion: 0.14
Nodes (3): FinancialReportService, Carbon, Illuminate\Database\Eloquent\Collection

### Community 14 - "app.js"
Cohesion: 0.11
Nodes (35): initAddressFieldSync(), syncAddressFieldsInForm(), bootApp(), exposeRichTextHelpers(), loadRichTextModule(), initFormPlugins(), initEntityCreateForm(), initEntityFormFields() (+27 more)

### Community 15 - "dependencies"
Cohesion: 0.05
Nodes (38): alpinejs, concurrently, flatpickr, laravel-vite-plugin, dependencies, flatpickr, @tiptap/core, @tiptap/extension-color (+30 more)

### Community 16 - "tomselect-init.js"
Cohesion: 0.12
Nodes (37): tom-select, initPersonForm(), initPersonsToggleLogic(), isSelectInVisibleSection(), refreshPersonFormSelects(), scheduleRefreshPersonFormSelects(), activateTomSelectsIn(), addNativeOptionToTomSelect() (+29 more)

### Community 17 - "EnsuresOperationalBusinessEntity.php"
Cohesion: 0.10
Nodes (6): ensureNotClosed(), ensureOperationalForAccounting(), TrackingCategoryController, TrackingSubCategoryController, TrackingCategory, TrackingSubCategory

### Community 18 - "Asset"
Cohesion: 0.09
Nodes (4): AssetShowWorkspaceController, Asset, down(), up()

### Community 20 - "Illuminate\Console\Command"
Cohesion: 0.09
Nodes (9): BackfillComplianceDueDates, BackfillPersonsEncryption, ReencryptModelAttributes, ScheduleBackup, SyncComplianceReminders, ComplianceReminderService, EncryptionHelper, Illuminate\Console\Command (+1 more)

### Community 21 - "Illuminate\Http\JsonResponse"
Cohesion: 0.12
Nodes (5): AdminUsersWorkspaceController, UserManagementController, EmailTemplateController, EntityShowWorkspaceController, Illuminate\Http\JsonResponse

### Community 22 - "PHPUnit\Framework\TestCase"
Cohesion: 0.08
Nodes (6): PHPUnit\Framework\TestCase, AssetDueDateRemindersTest, AssetLoanAccountLinkTest, BankAccountAccessTest, BankAccountAssetLinkServiceTest, TwoFactorServiceTest

### Community 23 - "bank-account-modal.js"
Cohesion: 0.10
Nodes (26): availablePurposes(), buildCreateFormUrl(), getSelectedAccountOption(), getSelectValue(), initBankAccountModal(), parseConfig(), purposesOnEntity(), bindBankFormFields() (+18 more)

### Community 24 - "EntityPerson"
Cohesion: 0.10
Nodes (3): EntityPersonController, PersonsIndexWorkspaceController, EntityPerson

### Community 25 - "ComplianceYearRecord"
Cohesion: 0.11
Nodes (5): EnsureComplianceYears, ComplianceYearRecord, ComplianceYearRecordPolicy, ComplianceYearService, Carbon

### Community 26 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.08
Nodes (3): ComplianceDocumentType, up(), Illuminate\Database\Eloquent\Relations\HasMany

### Community 28 - "Vendor"
Cohesion: 0.15
Nodes (3): VendorController, Vendor, VendorSyncService

### Community 29 - "User"
Cohesion: 0.09
Nodes (4): User, ComplianceDocumentFilePolicy, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 30 - "Person"
Cohesion: 0.12
Nodes (3): BankAccountPanelController, PersonShowWorkspaceController, Person

### Community 31 - "Note"
Cohesion: 0.09
Nodes (5): Note, RealEstateCompany, RealEstateCompanyContact, Tenant, up()

### Community 32 - "Lease"
Cohesion: 0.17
Nodes (5): AssetInvoiceController, InvoiceLine, Lease, Carbon, RentInvoiceService

### Community 33 - "Commitment"
Cohesion: 0.20
Nodes (3): CommitmentController, Commitment, CommitmentPayment

### Community 34 - "DocumentStorage"
Cohesion: 0.11
Nodes (5): DocumentStorage, UserFactory, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 35 - "ContactList"
Cohesion: 0.13
Nodes (4): ContactListController, ContactListsWorkspaceController, ContactListResource, ContactList

### Community 36 - "LoginRequest"
Cohesion: 0.13
Nodes (5): AuthenticatedSessionController, ProfileController, LoginRequest, ProfileUpdateRequest, Illuminate\Foundation\Http\FormRequest

### Community 38 - "Illuminate\View\View"
Cohesion: 0.17
Nodes (6): CarReportController, PropertyReportController, AppLayout, GuestLayout, Illuminate\View\Component, Illuminate\View\View

### Community 40 - "Closure"
Cohesion: 0.18
Nodes (8): EnsureAccountActive, EnsureSuperAdmin, Response, EnsureTwoFactorEnrolled, SecurityHeaders, TwoFactorVerified, Closure, Symfony\Component\HttpFoundation\Response

### Community 45 - "Illuminate\Support\Collection"
Cohesion: 0.18
Nodes (4): AssetSummaryReportService, CarReportService, Carbon, Illuminate\Support\Collection

### Community 48 - "transaction-paid-by-bank-account.js"
Cohesion: 0.21
Nodes (14): bindPaidBySelectChange(), bindPaidByTomSelectChange(), initTransactionPaidByBankAccount(), refreshTransactionPaidByBankAccount(), setupTransactionPaidByBankAccount(), clearPaidByClientErrors(), initTransactionPaidByValidation(), isEntityPaidBy() (+6 more)

### Community 52 - "Illuminate\Http\Resources\Json\JsonResource"
Cohesion: 0.17
Nodes (7): ComplianceCategoryResource, ComplianceDocumentFileResource, ComplianceDocumentTypeResource, ComplianceYearWorkspaceResource, DocumentCategoryResource, DocumentSlotResource, Illuminate\Http\Resources\Json\JsonResource

### Community 53 - "tiptap-init.js"
Cohesion: 0.26
Nodes (16): buildToolbar(), cleanupEditorShell(), createDivider(), createEditorShell(), createToolbarButton(), destroyRichTextEditor(), escapeSelectorId(), fieldValue() (+8 more)

### Community 54 - "MailMessage"
Cohesion: 0.23
Nodes (3): MailMessage, MsgParserService, Illuminate\Database\Eloquent\Relations\BelongsToMany

### Community 57 - "Illuminate\Contracts\Validation\ValidationRule"
Cohesion: 0.15
Nodes (5): UniqueAbnHash, UniqueAcnHash, UniqueChecklistLabelInCategory, UniqueComplianceLabelInCategory, Illuminate\Contracts\Validation\ValidationRule

### Community 62 - "scripts"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 63 - "Carbon\Carbon"
Cohesion: 0.37
Nodes (3): AtoDueDateService, Carbon, Carbon\Carbon

### Community 65 - "ReportEntityScopeResolver"
Cohesion: 0.21
Nodes (4): mergeReportFormScope(), resolveReportEntityIds(), ReportEntityScopeResolver, ReportEntityScopeResolverTest

### Community 68 - ".handle"
Cohesion: 0.22
Nodes (4): PasswordSecurity, HeaderSearchIndex, PasswordPolicy, Illuminate\Validation\Rules\Password

### Community 69 - "DocumentUploadValidation"
Cohesion: 0.22
Nodes (3): DocumentUploadValidation, Illuminate\Http\UploadedFile, DocumentUploadValidationTest

### Community 71 - "ComplianceReportController"
Cohesion: 0.33
Nodes (3): ComplianceReportController, Carbon, Symfony\Component\HttpFoundation\StreamedResponse

### Community 72 - "DocumentController.php"
Cohesion: 0.23
Nodes (6): backfillCategoriesAndLabels(), up(), backfillNullLabels(), deduplicateLabels(), reassignOrphans(), up()

### Community 74 - "EncryptsAttributes.php"
Cohesion: 0.38
Nodes (11): addEncryptedAttribute(), attributesToArray(), decrypt(), decryptAttributes(), encrypt(), encryptAttributes(), getAttribute(), getEncryptedAttributes() (+3 more)

### Community 75 - "business-entities/show.blade.php"
Cohesion: 0.17
Nodes (11): business-entities.partials.assets-workspace, business-entities.partials.bank-accounts.list, business-entities.partials.close-entity-modal, business-entities.partials.contact-lists-workspace, business-entities.partials.entity-details-sidebar, business-entities.partials.notes-workspace, business-entities.partials.persons-workspace, bank-accounts.partials.account-link-actions (+3 more)

### Community 76 - "python_bank_parser.py"
Cohesion: 0.24
Nodes (11): extract_entries(), find_column(), main(), parse_amount(), parse_date(), parse_file(), Find first matching column (case-insensitive)., Parse amount from string or number. Returns float. Positive = credit, negative… (+3 more)

### Community 77 - "form-saving-ui.js"
Cohesion: 0.30
Nodes (11): disableSubmitter(), ensureOverlay(), hideFormSaving(), initGlobalFormSaving(), isFormSaving(), isWorkspaceFormSaving(), lockFormFields(), restoreSubmitter() (+3 more)

### Community 79 - "Illuminate\Support\ServiceProvider"
Cohesion: 0.22
Nodes (5): AppServiceProvider, EncryptedFilesystemServiceProvider, Illuminate\Filesystem\FilesystemAdapter, Illuminate\Support\ServiceProvider, League\Flysystem\Filesystem

### Community 80 - "ChartOfAccountController"
Cohesion: 0.27
Nodes (3): ChartOfAccountController, RedirectResponse, View

### Community 83 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 84 - "require"
Cohesion: 0.18
Nodes (11): require, bacon/bacon-qr-code, fakerphp/faker, laravel/framework, laravel/tinker, league/flysystem, league/flysystem-aws-s3-v3, php (+3 more)

### Community 85 - "Illuminate\Database\Seeder"
Cohesion: 0.25
Nodes (4): ChartOfAccountSeeder, ComplianceDocumentTypeSeeder, DatabaseSeeder, Illuminate\Database\Seeder

### Community 88 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, laravel/breeze, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 89 - "TestCase"
Cohesion: 0.22
Nodes (4): CreatesApplication, Illuminate\Foundation\Testing\TestCase, TestCase, FinancialReportsHubPageTest

### Community 90 - "main"
Cohesion: 0.29
Nodes (9): check_python(), install_dependencies(), main(), Ensure Python 3 is available., Install requirements.txt., Verify all required packages can be imported., Verify both parser scripts exist., verify_imports() (+1 more)

### Community 98 - "parse_msg_simple.py"
Cohesion: 0.36
Nodes (7): format_date(), main(), parse_recipients(), parse_sender(), Extract name and email from sender (string or object)., Extract recipients as list of strings., Format datetime to ISO string.

### Community 108 - "assets/show.blade.php"
Cohesion: 0.29
Nodes (6): assets.partials.asset-details-sidebar, assets.partials.invoices-tab, assets.partials.linked-bank-accounts-show, assets.partials.loan-banking-show, business-entities.partials.compliance-workspace, business-entities.partials.documents-workspace

### Community 109 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 110 - "2026_06_22_000001_add_compliance_categories.php"
Cohesion: 0.48
Nodes (6): addLabelUniqueIndex(), backfillCategoriesAndFiles(), down(), dropLabelUniqueIndex(), seedCategoryGroups(), up()

### Community 111 - "financial-reports-hub.js"
Cohesion: 0.43
Nodes (3): buildReportNavigationUrl(), initFinancialReportsHub(), navigateToReport()

### Community 112 - "showToast"
Cohesion: 0.48
Nodes (6): dismissToast(), escapeHtml(), iconSvg(), showToast(), toastRoot(), TYPE_STYLES

### Community 115 - "parse_email"
Cohesion: 0.40
Nodes (5): get, post, health(), parse_email(), UploadFile

### Community 116 - "asset-create-form.js"
Cohesion: 0.60
Nodes (5): bootAssetCreateForms(), initAssetCreateForm(), initAssetTypeToggle(), initCollapsibleSections(), scrollToFirstError()

### Community 117 - "EncryptedEmailUserProvider.php"
Cohesion: 0.60
Nodes (3): EncryptedEmailUserProvider, Illuminate\Auth\EloquentUserProvider, Illuminate\Contracts\Auth\Authenticatable

### Community 118 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 119 - "persons/show.blade.php"
Cohesion: 0.40
Nodes (4): persons.partials.bank-accounts.list, persons.partials.person-bank-account-modal, persons.partials.roles-list, persons.partials.summary-stats

### Community 120 - "profile/edit.blade.php"
Cohesion: 0.40
Nodes (4): profile.partials.delete-user-form, profile.partials.two-factor-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 123 - "app.blade.php"
Cohesion: 0.50
Nodes (3): bank-accounts.partials.bank-account-panel-shell, business-entities.partials.entity-workspace-panel, layouts.navigation

### Community 125 - "2026_06_29_100001_add_loan_repayment_paying_purpose_to_bank_accounts.php"
Cohesion: 0.83
Nodes (3): alterAccountPurposeConstraint(), down(), up()

### Community 126 - "2026_07_26_163700_add_unique_index_to_users_email_hash.php"
Cohesion: 0.83
Nodes (3): down(), indexExists(), up()

### Community 127 - "2026_07_26_170000_null_on_delete_mail_user_fks.php"
Cohesion: 0.83
Nodes (3): down(), hasUserForeignKey(), up()

### Community 128 - "assets/create.blade.php"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 129 - "assets/edit.blade.php"
Cohesion: 0.50
Nodes (3): assets.partials.linked-bank-accounts-fields, assets.partials.loan-banking-fields, bank-accounts.partials.add-account-modal

### Community 130 - "transactions/create.blade.php"
Cohesion: 0.50
Nodes (3): partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 131 - "transactions/edit.blade.php"
Cohesion: 0.50
Nodes (3): partials.transaction-paid-by-fields, partials.transaction-type-select, partials.vendor-select

### Community 135 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 136 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

## Knowledge Gaps
- **180 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+175 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **107 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `BusinessEntity` connect `BusinessEntity` to `Illuminate\Http\Request`, `BankAccount`, `MailMessageController`, `ComplianceDocumentFile`, `CreateEncryptedBackup`, `Illuminate\Database\Eloquent\Model`, `Reminder`, `FinancialYear`, `web.php`, `FinancialReportService`, `EnsuresOperationalBusinessEntity.php`, `Asset`, `AssetController`, `Illuminate\Http\JsonResponse`, `EntityPerson`, `ComplianceYearRecord`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Person`, `Note`, `Lease`, `Commitment`, `DocumentStorage`, `ContactList`, `DocumentCategory`, `Illuminate\View\View`, `.rule`, `BusinessEntityAsicRenewalDueDateTest`, `Illuminate\Support\Collection`, `Invoice`, `BankAccountAssetLinkService`, `Document`, `FinancialReportController`, `PersonsWorkspaceController`, `ComplianceReportService`, `DocumentUploadService`, `Carbon\Carbon`, `ReportEntityScopeResolver`, `AssetsWorkspaceController`, `.handle`, `BusinessEntityRegistrationDateTest`, `ComplianceReportController`, `DocumentController.php`, `AustralianAddress`, `EncryptsAttributes.php`, `ChartOfAccountController`, `JournalEntry`, `BusinessEntityBankAccount`, `TransactionPayerResolver`, `.asicAnniversaryInYear`, `BusinessEntityPolicy`, `ContactListPolicy`, `DocumentUploadService.php`, `.canAccessEntity`?**
  _High betweenness centrality (0.204) - this node is a cross-community bridge._
- **Why does `Asset` connect `Asset` to `BusinessEntity`, `MailMessageController`, `ComplianceDocumentFile`, `Illuminate\Database\Eloquent\Model`, `Reminder`, `web.php`, `AssetController`, `PHPUnit\Framework\TestCase`, `ComplianceYearRecord`, `Note`, `Lease`, `Commitment`, `DocumentStorage`, `DocumentCategory`, `Illuminate\View\View`, `.rule`, `Illuminate\Support\Collection`, `BankAccountAssetLinkService`, `Document`, `DocumentUploadService`, `PropertyReportService`, `AssetsWorkspaceController`, `.handle`, `DocumentController.php`, `AssetPolicy`, `DocumentUploadService.php`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **Why does `ChartOfAccount` connect `ChartOfAccount` to `Illuminate\Http\Request`, `ContactList`, `CreateEncryptedBackup`, `Illuminate\Database\Eloquent\Model`, `TransactionPostingService`, `web.php`, `FinancialReportService`, `ChartOfAccountController`, `JournalEntry`, `JournalLine`, `Illuminate\Database\Seeder`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Are the 42 inferred relationships involving `BusinessEntity` (e.g. with `.handle()` and `.listHtmlForContext()`) actually correct?**
  _`BusinessEntity` has 42 INFERRED edges - model-reasoned connections that need verification._
- **Are the 24 inferred relationships involving `Asset` (e.g. with `.assetOperationalQuery()` and `.dashboard()`) actually correct?**
  _`Asset` has 24 INFERRED edges - model-reasoned connections that need verification._
- **Are the 23 inferred relationships involving `BankAccount` (e.g. with `.bankAccountPickerData()` and `.syncBankAccountLinks()`) actually correct?**
  _`BankAccount` has 23 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _180 weakly-connected nodes found - possible documentation gaps or missing edges._