# Asset Tracker

Laravel-based **Australian business entity, asset, and accounting portal**. It helps track companies/trusts and their assets, compliance dates, documents, banking, and related contacts — not a sales CRM (no leads, deals, or pipelines).

## Features

### Business entities
- Entity types: Sole Trader, Company, Trust, Partnership
- Compliance fields: ABN, ACN, TFN, ASIC renewal and related dates
- People linked via `entity_person` roles (Director, Secretary, Shareholder, Trustee, Beneficiary, Settlor, Owner)
- Trust **appointor** is set on the trust’s company profile (person or entity), not as a normal officer role — legacy `Appointor` rows on `entity_person` may still exist
- Optional **tenancy / property manager contact** flag (`exclude_from_financial_reports`): contact-only companies excluded from financial reports and officer roles
- Per-entity contact lists (address book), documents, notes, and compliance workspaces

**Contact models (do not conflate):**
- **Person** / **EntityPerson** — people with corporate roles on operating entities (encrypted PII)
- **ContactList** — per-entity address book only (separate from Person; not encrypted like Person)
- **Tenant** / **RealEstateCompany** — property occupants and agencies on assets
- **Vendor** — supplier/payee master for transactions (not sales customers)

### Asset management
- Multi-type assets: cars, houses (owned/rented), warehouses, land, offices, shops, real estate, suites
- Lifecycle tracking: acquisition, maintenance, insurance, registration, disposal
- Financial fields: acquisition cost, current value, rental income, depreciation
- Due dates: registration, insurance, service, council rates, land tax
- Leases and tenants (including real-estate company contacts)

### Accounting
- Chart of accounts and double-entry journals
- Financial reports: Profit & Loss, Balance Sheet, Cash Flow
- Bank accounts, transactions, and statement import (Python helpers)
- Invoices (including rent invoicing for leases)
- Tracking categories / sub-categories
- Vendors and commitments

### Documents & communication
- Document storage with categories/slots (local and optional AWS S3)
- Encrypted storage options for sensitive files
- Gmail sync (optional), email upload (`.eml` / `.msg`), templates, and allocation to entities/assets
- Reminders and bills/tasks for due dates

### Security & access
- Login via Laravel Breeze-style auth (**no public registration** — users are created by an administrator)
- Google 2FA (enrolment and challenge middleware on app routes)
- Field-level encryption for sensitive User / Person / BusinessEntity data
- Security headers, CSRF, and Eloquent parameterized queries
- Primary administrator controlled by `ADMIN_EMAIL` / `ADMIN_PASSWORD_HASH` (see `config/admin.php`)

**Access model today:** authenticated users share the portfolio (entity/asset policies are permissive). App-level RBAC / multi-tenant isolation is **not** implemented yet. “Roles” in the product mean corporate roles on `entity_person`, not permission roles for login users.

## Architecture

### Stack
| Layer | Choice |
|-------|--------|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, Vite 8 |
| UI libs | Tom Select, Flatpickr (`<x-date-input>` only — no jQuery date pickers), Tiptap, Lucide Blade icons |
| Database | PostgreSQL |
| Storage | Local and optional AWS S3 |
| Auth | Breeze-style sessions + Google 2FA |
| Ancillary | Python 3.8+ for bank statement import and `.msg` parsing |

### Key models
- **BusinessEntity** — core business unit
- **Asset** — multi-type asset tracking
- **Person** / **EntityPerson** — people and corporate roles on entities
- **ContactList** — per-entity address book (separate from Person)
- **Document**, **Note**, **Reminder**
- **BankAccount**, **Transaction**, **ChartOfAccount**, **JournalEntry** / **JournalLine**
- **Invoice** / **InvoiceLine**
- **Lease**, **Tenant**, **RealEstateCompany**
- **Vendor**, **Commitment**
- **MailMessage**, **EmailTemplate**, **EmailDraft**
- **TrackingCategory** / **TrackingSubCategory**

### Notable services
- `FinancialReportService` — P&L, Balance Sheet, Cash Flow
- `TwoFactorService` — Google 2FA
- `GmailFetcher` — Gmail API sync
- `MsgParserService` — `.eml` / `.msg` parsing
- `RentInvoiceService`, `InvoicePostingService`, `TransactionPostingService`

Further frontend notes: [`docs/TECH_UPDATE.md`](docs/TECH_UPDATE.md). ATO lodgement tracking: [`docs/ATO_LODGEMENT_TRACKING.md`](docs/ATO_LODGEMENT_TRACKING.md).

## Requirements

- PHP 8.3+
- Composer 2+
- Node.js **22+** (recommended) and npm
- PostgreSQL 13+
- Python 3.8+ locally (email `.msg` parsing and PDF statement parsing; bank CSV import is handled in PHP)
- Redis recommended for cache/queue (see `.env.example`)
- AWS S3 credentials if using cloud document storage
- Gmail API credentials if enabling email sync

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd assettracker
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Install Python dependencies** (email `.msg` parsing and PDF statement parsing)
   ```bash
   # Windows
   python\start.bat

   # Linux/macOS (chmod +x python/start.sh first)
   ./python/start.sh

   # Or:
   pip install -r python/requirements.txt
   ```

5. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

6. **Configure `.env`** (see `.env.example` for the full list). Common values:
   ```env
   APP_URL=http://localhost

   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=assettracker
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   # Primary admin portal login (override defaults in config/admin.php)
   ADMIN_EMAIL=
   ADMIN_PASSWORD_HASH=
   ADMIN_DEFAULT_NAME=

   # Encryption (optional; fall back to APP_KEY if unset)
   ENCRYPTION_KEY=
   DB_ENCRYPTION_KEY=
   BACKUP_ENCRYPTION_KEY=

   # Documents: production expects S3
   AWS_ACCESS_KEY_ID=
   AWS_SECRET_ACCESS_KEY=
   AWS_DEFAULT_REGION=
   AWS_BUCKET=
   DOCUMENTS_STORAGE_DISK=s3

   # Optional: Gmail sync
   GMAIL_ENABLED=false
   GMAIL_CLIENT_ID=
   GMAIL_CLIENT_SECRET=
   GMAIL_REFRESH_TOKEN=
   GMAIL_USER_EMAIL=
   GMAIL_LABEL=INBOX
   ```

7. **Run migrations** (optionally seed chart of accounts / sample data — see [Seeders](#seeders))
   ```bash
   php artisan migrate
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   # During UI work use: npm run dev
   ```

9. **Start the app**
   ```bash
   php artisan serve
   # or server + queue listener + log tail:
   composer run dev
   ```

### Local / XAMPP notes

### Production deploy

See [docs/PRODUCTION.md](docs/PRODUCTION.md) for server details, runtime versions (including production Python 3.11), and post-deploy steps (`npm ci`, `npm run build`).

`.env.example` is production-oriented. For local HTTP (e.g. `http://localhost` or XAMPP):

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/assettracker/public   # or http://127.0.0.1:8000 with artisan serve

FORCE_HTTPS=false
SESSION_SECURE_COOKIE=false

# If Redis is not running locally:
CACHE_STORE=database
QUEUE_CONNECTION=database
# jobs tables ship with default migrations
```

- PostgreSQL is still required (do not assume MySQL/MariaDB from XAMPP alone).
- On Windows, if S3 TLS fails, set `AWS_SSL_VERIFY` to a CA bundle (see comment in `.env.example`; default path `storage/app/cacert.pem`).
- Keep PHP `upload_max_filesize` / `post_max_size` at least as large as `DOCUMENTS_MAX_KB` (default 20 MB).

## Quick start

1. Sign in at `/login` with the configured admin credentials (or a user created by an admin)
2. Complete two-factor authentication setup when prompted (a few grace logins are allowed — see `config/admin.php`)
3. Create a business entity from the dashboard
4. Set up chart of accounts and bank accounts as needed
5. Add assets, persons, documents, and reminders
6. Optionally enable Gmail and/or import bank statements
7. Use **Reports** and **Portfolio** for financial / property overviews

### Creating users

There is no self-service registration. The primary administrator (email matching `ADMIN_EMAIL`) can manage users at **`/admin/users`** (create, activate/deactivate, reset password, delete). Other authenticated users cannot open that area.

## Main areas

| Area | Path | Notes |
|------|------|--------|
| Dashboard | `/dashboard` | Stats, reminders, quick entry, shortcuts |
| Bills & tasks | `/bills-tasks` | Due work across the portfolio |
| Business entities | `/business-entities` | Entity hub (persons, contacts, assets, banking, docs, compliance) |
| Persons | `/persons` | People directory and entity roles |
| Assets | `/assets` | Cross-entity asset list |
| Emails | `/emails` | Inbox, upload, allocate to entity/asset |
| Vendors | `/vendors` | Supplier contacts and transaction linking |
| Reminders | `/reminders` | Due-date reminders |
| Reports | `/financial-reports` | P&L, Balance Sheet, Cash Flow, compliance, cars, etc. |
| Portfolio | `/portfolio` | Property portfolio view |
| Admin users | `/admin/users` | Super-admin only |

Primary nav: Dashboard, Bills & tasks, Emails, Reports, Portfolio (plus user/admin menu).

## Seeders

```bash
php artisan db:seed
# or selectively:
php artisan db:seed --class=ChartOfAccountSeeder
php artisan db:seed --class=ComplianceDocumentTypeSeeder
```

`DatabaseSeeder` also creates a factory `test@example.com` user and sample email templates, then runs `ChartOfAccountSeeder`. Prefer selective seeders in shared environments if you do not want the test user.

## Email & bank import

### Gmail
- Dashboard → Emails
- When `GMAIL_ENABLED=false` or credentials are missing, sync uses a safe fallback/dummy path
- Upload accepts `.eml` / `.msg` (size limits apply); uploads are stored under `storage/app/emails/uploads/{user_id}`

### Bank statements
- Dashboard → Bank Import
- Upload CSV bank statements
- CSV parsing runs in PHP; Python helpers remain for PDF statements and email `.msg` files
- Excel import can return when Python 3.9+ is available on the server

## Database (key tables)

### Core
- `business_entities`, `persons`, `entity_person`, `contact_lists`
- `assets`, `leases`, `tenants`, `real_estate_companies`, `vendors`
- `documents`, `notes`, `reminders`, `commitments`

### Accounting
- `chart_of_accounts`, `transactions`, `journal_entries`, `journal_lines`
- `invoices`, `invoice_lines`, `bank_accounts`, `bank_statement_entries`
- `tracking_categories`, `tracking_sub_categories`

### Communication
- `mail_messages`, `mail_attachments`, `mail_labels`
- `email_templates`, `email_drafts`

There is **no** application `roles` permissions table. Corporate roles live on `entity_person.role`.

## API

The HTTP API surface is **minimal** today (`routes/api.php`):

- `GET /api/user` — Sanctum-authenticated current user (Sanctum may not be fully wired for SPA/token use)
- `GET /api/business-entities/{businessEntity}/bank-accounts` — bank accounts helper used by the UI

Most functionality is served by authenticated **web** routes (Blade + workspace JSON/HTML partials), not a public REST API.

## Development

```bash
# Tests
php artisan test
php artisan test --testsuite=Feature

# Formatting / analysis
./vendor/bin/pint
./vendor/bin/phpstan analyse

# Dev processes
composer run dev
php artisan pail
php artisan queue:work
```

## Roadmap (aspirational)

- Stronger per-user / per-organisation authorization (multi-tenancy)
- Broader first-party API for integrations
- Deeper analytics and custom reporting
- Optional third-party accounting connectors (e.g. Xero, QuickBooks)
- Mobile clients

## Support

- Framework questions: [Laravel docs](https://laravel.com/docs)
- Project frontend plan: [`docs/TECH_UPDATE.md`](docs/TECH_UPDATE.md)
- Bugs and requests: use your team’s issue tracker / GitHub Issues if enabled

---

Built with Laravel, Blade, Tailwind, and Alpine.js.
