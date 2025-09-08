# Asset Tracker

A comprehensive Laravel-based asset management and accounting system designed for business entities to track, manage, and maintain their assets with integrated financial management, document management, and business intelligence features.

## 🚀 Features

### Core Asset Management
- **Multi-Type Asset Support**: Cars, Houses (Owned/Rented), Warehouses, Land, Offices, Shops, Real Estate, Suites
- **Asset Lifecycle Tracking**: Acquisition, maintenance, insurance, registration, and disposal
- **Financial Tracking**: Acquisition costs, current values, rental income, and depreciation
- **Due Date Management**: Registration renewals, insurance renewals, service schedules, council rates, land tax
- **Lease Management**: Track rental agreements, tenants, and lease terms

### Business Entity Management
- **Entity Types**: Sole Trader, Company, Trust, Partnership
- **Compliance Tracking**: ABN, ACN, TFN, ASIC renewal dates
- **Contact Management**: Entity persons with multiple roles and responsibilities
- **Document Storage**: Centralized document management for all entities

### Advanced Accounting System
- **Chart of Accounts**: Complete double-entry accounting system
- **Financial Reports**: Profit & Loss, Balance Sheet, Cash Flow statements
- **Journal Entries**: Manual journal entry creation and management
- **Bank Statement Import**: Automated bank statement processing with Python backend
- **Transaction Posting**: Automatic transaction posting to chart of accounts
- **Tracking Categories**: Custom tracking categories and sub-categories
- **Invoice Management**: Create, manage, and post invoices
- **Rent Invoicing**: Automated rent invoice generation for leased properties

### Document Management
- **Multi-Format Support**: Document storage for various file types (images, documents, spreadsheets)
- **Centralized Storage**: Organized document management for all business entities
- **Secure Access**: Role-based access control for document viewing and management
- **Encrypted Storage**: Advanced file encryption for sensitive documents

### Financial Management
- **Bank Account Integration**: Multiple bank accounts per business entity
- **Transaction Tracking**: Manual and automated transaction entry
- **Receipt Management**: Digital receipt storage and categorization
- **Bank Statement Processing**: Import and reconcile bank statements
- **Depreciation Schedules**: Automated asset depreciation calculations

### Email & Communication
- **Gmail Integration**: Sync emails from Gmail accounts
- **Email Templates**: Pre-built templates for common communications
- **Contact Lists**: Organized contact management for business relationships
- **Email Upload**: Upload .eml and .msg email files
- **Email Allocation**: Link emails to business entities and assets

### Security & Authentication
- **Two-Factor Authentication**: Enhanced security with Google 2FA
- **Role-Based Access Control**: Granular permissions for different user types
- **Encrypted File Storage**: Advanced encryption for sensitive data
- **Security Headers**: Comprehensive security headers implementation
- **Encrypted Backups**: Secure backup system with encryption

### Cloud Storage Integration
- **AWS S3 Integration**: Secure cloud storage for documents and receipts
- **Encrypted Storage**: File-level encryption for sensitive documents
- **Local Storage**: Fallback to local storage when needed

### Reminder System
- **Smart Notifications**: Due date reminders for all asset types
- **Customizable Alerts**: Configurable reminder frequencies and priorities
- **Bulk Operations**: Mass reminder management and completion

## 🏗️ Architecture

### Technology Stack
- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade templates with Tailwind CSS
- **Database**: MySQL/PostgreSQL with comprehensive migrations

- **Cloud Storage**: AWS S3 support
- **Authentication**: Laravel Breeze with custom 2FA

### Key Models
- **BusinessEntity**: Core business unit management
- **Asset**: Multi-type asset tracking with type-specific fields
- **EntityPerson**: People associated with business entities
- **Document**: Centralized document management
- **Transaction**: Financial transaction tracking
- **Reminder**: Due date and task management
- **BankAccount**: Banking information and statements
- **ChartOfAccount**: Chart of accounts for accounting
- **JournalEntry/JournalLine**: Double-entry accounting system
- **Invoice/InvoiceLine**: Invoice management and processing
- **Lease**: Rental agreement management
- **Tenant**: Tenant information and management
- **MailMessage**: Email management and storage
- **EmailTemplate**: Email template system
- **ContactList**: Contact management
- **TrackingCategory/TrackingSubCategory**: Custom tracking system

### Services
- **FinancialReportService**: Generate financial reports (P&L, Balance Sheet, Cash Flow)
- **TwoFactorService**: Google 2FA implementation
- **GmailFetcher**: Gmail API integration
- **MsgParserService**: Email file parsing (.eml/.msg)
- **RentInvoiceService**: Automated rent invoice generation
- **InvoicePostingService**: Invoice posting to accounting system
- **TransactionPostingService**: Transaction posting automation
- **FileHelper**: File management utilities
- **UrlHelper**: URL generation and validation

## 📋 Requirements

- PHP 8.2 or higher
- Composer 2.0 or higher
- Node.js 18+ and npm
- MySQL 8.0+ or PostgreSQL 13+
- Python 3.8+ (for bank statement processing)
- AWS S3 credentials (for cloud storage)

## 🛠️ Installation

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

4. **Install Python dependencies**
   ```bash
   # Windows
   install_python_deps.bat
   
   # Linux/Mac
   chmod +x install_python_deps.sh
   ./install_python_deps.sh
   
   # Manual installation
   pip install pandas openpyxl xlrd
   ```

5. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

6. **Configure environment variables**
   ```env
   # Database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=assettracker
   DB_USERNAME=your_username
   DB_PASSWORD=your_password



   # AWS S3
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=your_aws_region
AWS_BUCKET=your_bucket_name

   # AWS S3 (optional)
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   AWS_DEFAULT_REGION=your_aws_region
   AWS_BUCKET=your_bucket_name
   
   # Gmail API (for Emails section)
   GMAIL_ENABLED=false
   GMAIL_CLIENT_ID=
   GMAIL_CLIENT_SECRET=
   GMAIL_REFRESH_TOKEN=
   GMAIL_USER_EMAIL=
   GMAIL_LABEL=INBOX
   ```

7. **Run database migrations**
   ```bash
   php artisan migrate
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

## 📧 Email Integration

### Gmail Integration
- Access via Dashboard → Emails
- Sync Gmail: uses credentials above. When `GMAIL_ENABLED=false` or creds missing, a dummy sync runs.
- Upload emails: Emails → Upload; accepts `.eml`/`.msg` files (10MB each). Files stored under `storage/app/emails/uploads/{user_id}` and listed as `uploaded`.

### Bank Statement Import
- Access via Dashboard → Bank Import
- Upload Excel (.xlsx, .xls) or CSV bank statement files
- Automatic parsing and matching to chart of accounts
- Manual override capabilities for precise control

## 🚀 Quick Start

1. **Register a new account** at `/register`
2. **Complete two-factor authentication** setup
3. **Create your first business entity** with company details
4. **Set up chart of accounts** for your accounting system
5. **Add assets** (cars, properties, equipment, suites)
6. **Upload documents** and manage them securely
7. **Set up reminders** for important due dates
8. **Import bank statements** and reconcile transactions
9. **Generate financial reports** (P&L, Balance Sheet)
10. **Set up email integration** for communication

## 📱 Usage Examples

### Creating a Business Entity
```php
// Business entity with company details
$entity = BusinessEntity::create([
    'legal_name' => 'Acme Corporation Pty Ltd',
    'entity_type' => 'Company',
    'abn' => '12345678901',
    'acn' => '123456789',
    'registered_address' => '123 Business St, Sydney NSW 2000'
]);
```

### Adding an Asset
```php
// Car asset with registration and insurance
$asset = $entity->assets()->create([
    'asset_type' => 'Car',
    'name' => 'Company Fleet Vehicle',
    'registration_number' => 'ABC123',
    'registration_due_date' => '2024-12-31',
    'insurance_due_date' => '2024-06-30'
]);
```



## 🔧 Development

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
```

### Code Quality
```bash
# Code formatting
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```

### Development Commands
```bash
# Start development environment
composer run dev

# Monitor logs
php artisan pail

# Queue processing
php artisan queue:work
```

## 📊 Database Schema

The application uses a comprehensive database schema with the following key tables:

### Core Business Tables
- **business_entities**: Core business information
- **entity_persons**: People and roles within entities
- **persons**: Individual person records
- **contact_lists**: Contact management

### Asset Management Tables
- **assets**: Multi-type asset management
- **asset_documents**: Asset-specific document links
- **leases**: Rental agreement management
- **tenants**: Tenant information
- **depreciation_schedules**: Asset depreciation tracking

### Accounting System Tables
- **chart_of_accounts**: Chart of accounts structure
- **transactions**: Financial transaction records
- **journal_entries**: Double-entry journal entries
- **journal_lines**: Individual journal entry lines
- **invoices**: Invoice management
- **invoice_lines**: Invoice line items
- **bank_accounts**: Banking information
- **bank_statement_entries**: Bank statement processing
- **tracking_categories**: Custom tracking categories
- **tracking_sub_categories**: Tracking sub-categories

### Communication Tables
- **mail_messages**: Email storage and management
- **mail_attachments**: Email attachment storage
- **mail_labels**: Email label management
- **email_templates**: Email template system
- **email_drafts**: Draft email storage

### System Tables
- **documents**: File storage and metadata
- **reminders**: Due date management
- **notes**: General notes and comments
- **due_dates**: Due date tracking
- **roles**: User role management

## 🔐 Security Features

- **Two-Factor Authentication**: Google 2FA with backup codes
- **Encrypted Data Storage**: Field-level encryption for sensitive data
- **Encrypted File Storage**: Advanced file encryption system
- **Security Headers**: Comprehensive security headers implementation
- **Encrypted Backups**: Secure backup system with encryption
- **CSRF Protection**: Built-in Laravel CSRF token validation
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **File Upload Security**: Secure file handling and validation
- **Role-Based Access**: Granular permission system
- **Environment Variable Encryption**: Encrypted configuration management

## 🌐 API Endpoints

The application provides RESTful API endpoints for:

- **Business Entity Management**: CRUD operations for business entities
- **Asset Operations**: Asset management and tracking
- **Accounting System**: Chart of accounts, transactions, journal entries
- **Financial Reports**: Profit & Loss, Balance Sheet, Cash Flow
- **Document Management**: File uploads and retrieval
- **Email Integration**: Gmail sync and email management
- **Bank Import**: Bank statement processing
- **Invoice Management**: Invoice creation and processing
- **Reminder System**: Due date and task management
- **Contact Management**: Contact lists and person management

## 📈 Performance

- **Database Optimization**: Efficient queries with proper indexing
- **File Caching**: Intelligent file caching and storage
- **Queue Processing**: Background job processing for heavy operations
- **Asset Compilation**: Optimized frontend asset building

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:

- **Documentation**: Check the Laravel documentation for framework-specific questions
- **Issues**: Report bugs and feature requests through GitHub Issues
- **Community**: Join Laravel community forums and discussions

## 🔮 Roadmap

- **Mobile App**: Native mobile applications for iOS and Android
- **Advanced Analytics**: Enhanced business intelligence and reporting dashboard
- **Integration APIs**: Third-party service integrations (Xero, QuickBooks)
- **Multi-Tenancy**: Support for multiple organizations
- **Advanced Reporting**: Custom report builder and scheduling
- **API Enhancements**: RESTful API for third-party integrations
- **Workflow Automation**: Automated business process workflows
- **Advanced Security**: Additional security features and compliance tools

---

**Built with ❤️ using Laravel and modern web technologies**
