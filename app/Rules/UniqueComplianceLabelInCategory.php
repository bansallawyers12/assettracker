<?php

namespace App\Rules;

use App\Models\ComplianceDocumentFile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueComplianceLabelInCategory implements ValidationRule
{
    public function __construct(
        private int $categoryId,
        private ?int $excludeFileId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = ComplianceDocumentFile::query()
            ->where('compliance_category_id', $this->categoryId)
            ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(TRIM(?))', [trim((string) $value)]);

        if ($this->excludeFileId !== null) {
            $query->where('id', '!=', $this->excludeFileId);
        }

        if ($query->exists()) {
            $fail("A checklist row named \"{$value}\" already exists in this category.");
        }
    }
}
