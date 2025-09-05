<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
	protected $fillable = [
		'invoice_id',
		'description',
		'quantity',
		'unit_price',
		'line_total',
		'gst_rate',
		'account_code',
	];

	protected $casts = [
		'quantity' => 'decimal:4',
		'unit_price' => 'decimal:4',
		'line_total' => 'decimal:2',
		'gst_rate' => 'decimal:4',
	];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}
}
