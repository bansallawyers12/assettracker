<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceDocumentFile extends Model
{
    protected $fillable = [
        'compliance_year_record_id',
        'compliance_document_type_id',
        'status',
        'due_date',
        'lodged_date',
        'paid_date',
        'file_name',
        'path',
        'filetype',
        'file_size',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date'    => 'date',
            'lodged_date' => 'date',
            'paid_date'   => 'date',
            'file_size'   => 'integer',
        ];
    }

    public function yearRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceYearRecord::class, 'compliance_year_record_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ComplianceDocumentType::class, 'compliance_document_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasFile(): bool
    {
        return filled($this->path);
    }
}
