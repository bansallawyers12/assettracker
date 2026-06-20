<?php

namespace App\Rules;

use App\Models\Document;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueChecklistLabelInCategory implements ValidationRule
{
    public function __construct(
        private int $categoryId,
        private ?int $excludeDocumentId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Document::query()
            ->where('document_category_id', $this->categoryId)
            ->whereRaw('LOWER(TRIM(checklist_label)) = LOWER(TRIM(?))', [trim((string) $value)]);

        if ($this->excludeDocumentId !== null) {
            $query->where('id', '!=', $this->excludeDocumentId);
        }

        if ($query->exists()) {
            $fail("A checklist row named \"{$value}\" already exists in this category.");
        }
    }
}
