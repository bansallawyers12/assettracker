<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    protected $fillable = [
        'business_entity_id',
        'lease_id',
        'asset_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'customer_name',
        'reference',
        'subtotal',
        'gst_amount',
        'gst_basis',
        'total_amount',
        'notes',
        'currency',
        'status',
        'is_posted',
        'paid_at',
        'payment_method',
        'payment_reference',
        'payment_transaction_id',
        'last_reminder_sent_at',
        'reminder_count',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_posted' => 'boolean',
        'paid_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer',
    ];

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'payment_transaction_id');
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Suggest the next manual invoice number for an entity/month: INV{entityId}-YYYYMM###.
     */
    public static function suggestNumber(BusinessEntity $businessEntity, CarbonInterface|string|null $date = null): string
    {
        $issueDate = $date instanceof CarbonInterface
            ? Carbon::instance($date)->startOfDay()
            : Carbon::parse($date ?? now())->startOfDay();

        $prefix = 'INV'.$businessEntity->id.'-'.$issueDate->format('Ym');

        $lastInvoiceNumber = static::query()
            ->where('business_entity_id', $businessEntity->id)
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;
        if (is_string($lastInvoiceNumber) && preg_match('/(\d{3})$/', $lastInvoiceNumber, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public static $statuses = [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'void' => 'Void',
    ];
}
