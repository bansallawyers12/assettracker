<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    protected $fillable = [
        'business_entity_id', 'asset_id', 'related_entity_id', 'date', 'amount', 'description',
        'transaction_type', 'gst_amount', 'gst_status', 'receipt_path', 'document_id',
        'bank_account_id', 'tracking_category_id', 'tracking_sub_category_id',
        'invoice_number', 'payment_status', 'due_date', 'paid_at', 'payment_method',
        'paid_by', 'payment_document_id',
    ];

    protected $casts = [
        'date'     => 'date',
        'due_date' => 'date',
        'paid_at'  => 'date',
        'amount'     => 'decimal:2',
        'gst_amount' => 'decimal:2',
    ];

    /** All transaction types with display labels */
    public static $transactionTypes = [
        'sales_revenue'                => 'Sales Revenue',
        'interest_income'              => 'Interest Income',
        'rental_income'                => 'Rental Income',
        'grants_subsidies'             => 'Grants/Subsidies',
        'directors_loans_to_company'   => 'Directors\' Loans to Company',
        'cogs'                         => 'Cost of Goods Sold (COGS)',
        'wages_superannuation'         => 'Wages and Superannuation',
        'rent_utilities'               => 'Rent and Utilities',
        'marketing_advertising'        => 'Marketing/Advertising',
        'travel_expenses'              => 'Travel Expenses',
        'loan_repayments'              => 'Loan Repayments',
        'capital_expenditure'          => 'Capital Expenditure',
        'bas_payments'                 => 'BAS Payments',
        'repayment_directors_loans'    => 'Repayment of Directors\' Loans',
        'company_loans_to_directors'   => 'Company Loans to Directors (Division 7A)',
        'directors_fees'               => 'Directors\' Fees',
        'rent_to_related_party'        => 'Rent to Related Party',
        'purchases_from_related_party' => 'Purchases from Related Party',
        'sales_to_related_party'       => 'Sales to Related Party',
    ];

    /** Income transaction types */
    public static $incomeTypes = [
        'sales_revenue'              => 'Sales Revenue',
        'interest_income'            => 'Interest Income',
        'rental_income'              => 'Rental Income',
        'grants_subsidies'           => 'Grants/Subsidies',
        'sales_to_related_party'     => 'Sales to Related Party',
        'directors_loans_to_company' => 'Directors\' Loans to Company',
    ];

    /** Expense transaction types */
    public static $expenseTypes = [
        'cogs'                         => 'Cost of Goods Sold (COGS)',
        'wages_superannuation'         => 'Wages and Superannuation',
        'rent_utilities'               => 'Rent and Utilities',
        'marketing_advertising'        => 'Marketing/Advertising',
        'travel_expenses'              => 'Travel Expenses',
        'loan_repayments'              => 'Loan Repayments',
        'capital_expenditure'          => 'Capital Expenditure',
        'bas_payments'                 => 'BAS Payments',
        'repayment_directors_loans'    => 'Repayment of Directors\' Loans',
        'company_loans_to_directors'   => 'Company Loans to Directors (Division 7A)',
        'directors_fees'               => 'Directors\' Fees',
        'rent_to_related_party'        => 'Rent to Related Party',
        'purchases_from_related_party' => 'Purchases from Related Party',
    ];

    /** Payment method options */
    public static $paymentMethods = [
        'bank_transfer' => 'Bank Transfer',
        'cash'          => 'Cash',
        'card'          => 'Card',
        'bpay'          => 'BPAY',
        'other'         => 'Other',
    ];

    /** GST status display labels */
    public static $gstStatusLabels = [
        'included'     => 'Included',
        'excluded'     => 'Excluded',
        'gst_free'     => 'GST Free',
        'collected'    => 'Collected',
        'input_credit' => 'Input Credit',
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
