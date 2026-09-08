<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'payment_transaction_id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(InvoicePaymentAllocation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function amountPaid(): float
    {
        if ($this->relationLoaded('paymentAllocations')) {
            return round((float) $this->paymentAllocations->sum('amount'), 2);
        }

        return round((float) $this->paymentAllocations()->sum('amount'), 2);
    }

    public function amountDue(): float
    {
        return round(max(0, (float) $this->total_amount - $this->amountPaid()), 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->amountDue() <= 0.0001;
    }

    public function hasPaymentAllocations(): bool
    {
        if ($this->relationLoaded('paymentAllocations')) {
            return $this->paymentAllocations->isNotEmpty();
        }

        return $this->paymentAllocations()->exists();
    }

    /**
     * Refresh status / paid_at / last payment FK from current allocations.
     */
    public function syncPaymentStateFromAllocations(?string $paidAt = null, ?string $paymentMethod = null, ?string $paymentReference = null): void
    {
        // Always re-query allocations; callers may have just inserted/deleted rows.
        $this->unsetRelation('paymentAllocations');

        $paid = $this->amountPaid();
        $due = round(max(0, (float) $this->total_amount - $paid), 2);
        $lastAllocation = $this->paymentAllocations()
            ->orderByDesc('id')
            ->first();

        $updates = [
            'payment_transaction_id' => $lastAllocation?->transaction_id,
        ];

        if ($due <= 0.0001 && $paid > 0) {
            $updates['status'] = 'paid';
            $updates['paid_at'] = $paidAt ?? $this->paid_at ?? now();
            if ($paymentMethod !== null) {
                $updates['payment_method'] = $paymentMethod;
            }
            if ($paymentReference !== null) {
                $updates['payment_reference'] = $paymentReference;
            }
        } elseif ($paid > 0) {
            $updates['status'] = 'partial';
            $updates['paid_at'] = null;
            if ($paymentMethod !== null) {
                $updates['payment_method'] = $paymentMethod;
            }
            if ($paymentReference !== null) {
                $updates['payment_reference'] = $paymentReference;
            }
        } else {
            $updates['status'] = 'approved';
            $updates['paid_at'] = null;
            $updates['payment_method'] = null;
            $updates['payment_reference'] = null;
            $updates['payment_transaction_id'] = null;
        }

        $this->update($updates);
    }

    /**
     * Posted invoices that still sit on AR and can be matched to a bank credit.
     *
     * @param  list<int>  $businessEntityIds
     * @return Collection<int, self>
     */
    public static function unpaidPostedForMatching(array $businessEntityIds): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $businessEntityIds))));
        if ($ids === []) {
            return collect();
        }

        $paidSub = DB::table('invoice_payment_allocations')
            ->select('invoice_id', DB::raw('COALESCE(SUM(amount), 0) as amount_paid'))
            ->groupBy('invoice_id');

        return static::query()
            ->whereIn('business_entity_id', $ids)
            ->where('is_posted', true)
            ->whereIn('status', ['approved', 'partial'])
            ->leftJoinSub($paidSub, 'alloc_totals', function ($join) {
                $join->on('invoices.id', '=', 'alloc_totals.invoice_id');
            })
            ->whereRaw('(invoices.total_amount - COALESCE(alloc_totals.amount_paid, 0)) > 0.005')
            ->select('invoices.*')
            ->with('paymentAllocations')
            ->orderByDesc('invoices.issue_date')
            ->orderByDesc('invoices.id')
            ->limit(200)
            ->get();
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
        'partial' => 'Partially paid',
        'paid' => 'Paid',
        'void' => 'Void',
    ];
}
