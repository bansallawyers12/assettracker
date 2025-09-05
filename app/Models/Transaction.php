<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'business_entity_id', 'related_entity_id', 'date', 'amount', 'description',
        'transaction_type', 'gst_amount', 'gst_status', 'receipt_path',
        'bank_account_id', 'tracking_category_id', 'tracking_sub_category_id'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
    ];

    public static $transactionTypes = [
        'sales_revenue' => 'Sales Revenue',
        'interest_income' => 'Interest Income',
        'rental_income' => 'Rental Income',
        'grants_subsidies' => 'Grants/Subsidies',
        'directors_loans_to_company' => 'Directors\' Loans to Company',
        'cogs' => 'Cost of Goods Sold (COGS)',
        'wages_superannuation' => 'Wages and Superannuation',
        'rent_utilities' => 'Rent and Utilities',
        'marketing_advertising' => 'Marketing/Advertising',
        'travel_expenses' => 'Travel Expenses',
        'loan_repayments' => 'Loan Repayments',
        'capital_expenditure' => 'Capital Expenditure',
        'bas_payments' => 'BAS Payments',
        'repayment_directors_loans' => 'Repayment of Directors\' Loans',
        'company_loans_to_directors' => 'Company Loans to Directors (Division 7A)',
        'directors_fees' => 'Directors\' Fees',
        'rent_to_related_party' => 'Rent to Related Party',
        'purchases_from_related_party' => 'Purchases from Related Party',
        'sales_to_related_party' => 'Sales to Related Party',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
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