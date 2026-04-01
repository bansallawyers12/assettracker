<?php

namespace App\Models;

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
        'date'     => 'date',
        'due_date' => 'date',
        'paid_at'  => 'date',
        'amount'     => 'decimal:2',
        'gst_amount' => 'decimal:2',
    ];

    /** All transaction types with display labels */
    public static $transactionTypes = [
        // Income
        'rental_income'             => 'Rental Income',
        'reimbursement_of_expenses' => 'Reimbursement of Expenses',
        'interest_income'           => 'Interest Income',
        'other_income'              => 'Other Income',
        'asset_sales'               => 'Asset Sales',
        'director_loan_in'          => 'Director Loan In',
        // Expense
        'water_service_expenses'    => 'Water Service Expenses',
        'management_fees'           => 'Management Fees',
        'land_tax'                  => 'Land Tax',
        'valuation_and_rates'       => 'Valuation & Rates',
        'oc_fees'                   => 'OC Fees',
        'repairs_maintenance'       => 'Repairs & Maintenance',
        'other_expenses'            => 'Other Expenses',
        'asset_purchase'            => 'Asset Purchase',
        'other_personal_expenses'   => 'Other / Personal Expenses',
        'director_loan_out'         => 'Director Loan Out',
        'director_loan_repayment'   => 'Director Loan Repayment',
    ];

    /** Income transaction types */
    public static $incomeTypes = [
        'rental_income'             => 'Rental Income',
        'reimbursement_of_expenses' => 'Reimbursement of Expenses',
        'interest_income'           => 'Interest Income',
        'other_income'              => 'Other Income',
        'asset_sales'               => 'Asset Sales',
        'director_loan_in'          => 'Director Loan In',
    ];

    /** Expense transaction types */
    public static $expenseTypes = [
        'water_service_expenses'  => 'Water Service Expenses',
        'management_fees'         => 'Management Fees',
        'land_tax'                => 'Land Tax',
        'valuation_and_rates'     => 'Valuation & Rates',
        'oc_fees'                 => 'OC Fees',
        'repairs_maintenance'     => 'Repairs & Maintenance',
        'other_expenses'          => 'Other Expenses',
        'asset_purchase'          => 'Asset Purchase',
        'other_personal_expenses' => 'Other / Personal Expenses',
        'director_loan_out'       => 'Director Loan Out',
        'director_loan_repayment' => 'Director Loan Repayment',
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
