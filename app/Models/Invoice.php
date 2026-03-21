<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
		'total_amount',
		'notes',
		'currency',
		'status',
		'is_posted',
		'paid_at',
		'payment_method',
		'payment_reference',
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

	public function lines()
	{
		return $this->hasMany(InvoiceLine::class);
	}

	public static $statuses = [
		'draft' => 'Draft',
		'approved' => 'Approved',
		'paid' => 'Paid',
		'void' => 'Void',
	];
}
