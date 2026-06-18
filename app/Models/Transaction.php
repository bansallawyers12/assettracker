<?php

namespace App\Models;

use App\Support\TransactionPayerResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    protected $fillable = [
        'business_entity_id', 'asset_id', 'related_entity_id', 'date', 'amount', 'description', 'vendor_name',
        'transaction_type', 'gst_amount', 'gst_status', 'gst_basis', 'receipt_path', 'document_id',
        'bank_account_id', 'tracking_category_id', 'tracking_sub_category_id',
        'invoice_number', 'payment_status', 'due_date', 'paid_at', 'payment_method',
        'paid_by', 'payment_document_id',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
    ];

    /** Income transaction types */
    public static $incomeTypes = [
        'sales_revenue' => 'Sales Revenue',
        'rental_income' => 'Rental Income',
        'reimbursement_of_expenses' => 'Reimbursement of Expenses',
        'interest_income' => 'Interest Income',
        'other_income' => 'Other Income',
        'asset_sales' => 'Asset Sales',
        'director_loan_in' => 'Director Loan In',
        // Legacy / import aliases
        'grants_subsidies' => 'Grants & Subsidies',
        'sales_to_related_party' => 'Sales to Related Party',
        'directors_loans_to_company' => 'Director Loan from Director (import)',
    ];

    /** Expense transaction types */
    public static $expenseTypes = [
        'water_service_expenses' => 'Water Service Expenses',
        'management_fees' => 'Management Fees',
        'legal_expenses' => 'Legal Expenses',
        'land_tax' => 'Land Tax',
        'valuation_and_rates' => 'Valuation & Rates',
        'oc_fees' => 'OC Fees',
        'repairs_maintenance' => 'Repairs & Maintenance',
        'wages_salaries' => 'Wages & Salaries',
        'wages_superannuation' => 'Wages & Superannuation (combined)',
        'superannuation' => 'Superannuation',
        'payg_payment' => 'PAYG Payment',
        'bas_payments' => 'BAS / Tax Payment',
        'other_expenses' => 'Other Expenses',
        'asset_purchase' => 'Asset Purchase',
        'other_personal_expenses' => 'Other / Personal Expenses',
        'director_loan_out' => 'Director Loan Out',
        'director_loan_repayment' => 'Director Loan Repayment',
        // Legacy / import aliases
        'cogs' => 'Cost of Goods Sold',
        'capital_expenditure' => 'Capital Expenditure',
        'rent_utilities' => 'Rent & Utilities',
        'marketing_advertising' => 'Marketing & Advertising',
        'travel_expenses' => 'Travel Expenses',
        'loan_repayments' => 'Loan Repayment',
        'directors_fees' => 'Directors Fees',
        'rent_to_related_party' => 'Rent to Related Party',
        'purchases_from_related_party' => 'Purchase from Related Party',
        'repayment_directors_loans' => 'Director Loan Repayment (import)',
        'company_loans_to_directors' => 'Loan to Director (import)',
    ];

    /**
     * All transaction types merged. Always available — no DB query needed.
     * Use this instead of $transactionTypes directly in validation and views.
     */
    public static function allTypes(): array
    {
        return array_merge(static::$incomeTypes, static::$expenseTypes);
    }

    /**
     * Grouped options for transaction type select (label => key => display).
     *
     * @return array<string, array<string, string>>
     */
    public static function typeSelectGroups(): array
    {
        return [
            'Income' => [
                'sales_revenue' => self::$incomeTypes['sales_revenue'],
                'rental_income' => self::$incomeTypes['rental_income'],
                'reimbursement_of_expenses' => self::$incomeTypes['reimbursement_of_expenses'],
                'interest_income' => self::$incomeTypes['interest_income'],
                'other_income' => self::$incomeTypes['other_income'],
                'asset_sales' => self::$incomeTypes['asset_sales'],
                'grants_subsidies' => self::$incomeTypes['grants_subsidies'],
                'sales_to_related_party' => self::$incomeTypes['sales_to_related_party'],
            ],
            'Tax & payroll' => [
                'wages_salaries' => self::$expenseTypes['wages_salaries'],
                'wages_superannuation' => self::$expenseTypes['wages_superannuation'],
                'superannuation' => self::$expenseTypes['superannuation'],
                'payg_payment' => self::$expenseTypes['payg_payment'],
                'bas_payments' => self::$expenseTypes['bas_payments'],
            ],
            'Property & operating' => [
                'water_service_expenses' => self::$expenseTypes['water_service_expenses'],
                'management_fees' => self::$expenseTypes['management_fees'],
                'legal_expenses' => self::$expenseTypes['legal_expenses'],
                'land_tax' => self::$expenseTypes['land_tax'],
                'valuation_and_rates' => self::$expenseTypes['valuation_and_rates'],
                'oc_fees' => self::$expenseTypes['oc_fees'],
                'repairs_maintenance' => self::$expenseTypes['repairs_maintenance'],
                'rent_utilities' => self::$expenseTypes['rent_utilities'],
                'marketing_advertising' => self::$expenseTypes['marketing_advertising'],
                'travel_expenses' => self::$expenseTypes['travel_expenses'],
            ],
            'Director & related party' => [
                'director_loan_in' => self::$incomeTypes['director_loan_in'],
                'director_loan_out' => self::$expenseTypes['director_loan_out'],
                'director_loan_repayment' => self::$expenseTypes['director_loan_repayment'],
                'directors_loans_to_company' => self::$incomeTypes['directors_loans_to_company'],
                'repayment_directors_loans' => self::$expenseTypes['repayment_directors_loans'],
                'company_loans_to_directors' => self::$expenseTypes['company_loans_to_directors'],
                'directors_fees' => self::$expenseTypes['directors_fees'],
                'rent_to_related_party' => self::$expenseTypes['rent_to_related_party'],
                'purchases_from_related_party' => self::$expenseTypes['purchases_from_related_party'],
            ],
            'Other' => [
                'other_expenses' => self::$expenseTypes['other_expenses'],
                'other_personal_expenses' => self::$expenseTypes['other_personal_expenses'],
                'asset_purchase' => self::$expenseTypes['asset_purchase'],
                'capital_expenditure' => self::$expenseTypes['capital_expenditure'],
                'cogs' => self::$expenseTypes['cogs'],
                'loan_repayments' => self::$expenseTypes['loan_repayments'],
            ],
        ];
    }

    /** Transaction types used for entity summary “Super Paid” row. */
    public static function superPaymentTypes(): array
    {
        return ['superannuation', 'wages_superannuation'];
    }

    /** Transaction types used for entity summary PAYG row. */
    public static function paygPaymentTypes(): array
    {
        return ['payg_payment', 'bas_payments'];
    }

    /** Payment method options */
    public static $paymentMethods = [
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'card' => 'Card',
        'bpay' => 'BPAY',
        'other' => 'Other',
    ];

    /** GST status display labels */
    public static $gstStatusLabels = [
        'included' => 'Included',
        'excluded' => 'Excluded',
        'gst_free' => 'GST Free',
        'collected' => 'Collected',
        'input_credit' => 'Input Credit',
    ];

    /** How the amount relates to GST (10%) */
    public static $gstBasisLabels = [
        'inclusive' => 'GST inclusive (total includes 10% GST)',
        'exclusive' => 'GST exclusive (10% added on top)',
    ];

    /**
     * Derive direction ('income' or 'expense') from a transaction type key.
     */
    public static function directionFromType(string $type): string
    {
        return array_key_exists($type, static::$incomeTypes) ? 'income' : 'expense';
    }

    /**
     * Get the direction of this transaction instance.
     */
    public function getDirectionAttribute(): string
    {
        return static::directionFromType((string) $this->transaction_type);
    }

    public function getPaidByDisplayAttribute(): string
    {
        return TransactionPayerResolver::paidByLabel($this->paid_by);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function receiptDocument()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function paymentDocument()
    {
        return $this->belongsTo(Document::class, 'payment_document_id');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (! $this->receipt_path) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl($this->receipt_path, now()->addMinutes(30));
    }

    public function relatedEntity()
    {
        return $this->belongsTo(BusinessEntity::class, 'related_entity_id');
    }

    public function bankStatementEntries()
    {
        return $this->hasMany(BankStatementEntry::class);
    }

    public function trackingCategory()
    {
        return $this->belongsTo(TrackingCategory::class);
    }

    public function trackingSubCategory()
    {
        return $this->belongsTo(TrackingSubCategory::class);
    }

    public function scopeUnmatched($query)
    {
        return $query->whereNotIn('id', BankStatementEntry::pluck('transaction_id'));
    }
}
