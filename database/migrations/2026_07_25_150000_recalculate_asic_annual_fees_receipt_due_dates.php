<?php

use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceDocumentType;
use App\Services\AtoDueDateService;
use Illuminate\Database\Migrations\Migration;

/**
 * Correct due dates left as ATO (31 Oct / 15 May) after the annual_accounts
 * conversion used a stale type code during estimation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $type = ComplianceDocumentType::query()
            ->where('code', 'asic_annual_fees_receipt')
            ->first();

        if ($type === null) {
            return;
        }

        $dueDates = new AtoDueDateService;

        ComplianceDocumentFile::query()
            ->where('compliance_document_type_id', $type->id)
            ->with(['yearRecord.businessEntity'])
            ->orderBy('id')
            ->each(function (ComplianceDocumentFile $file) use ($dueDates, $type): void {
                $record = $file->yearRecord;
                if ($record === null || $record->asset_id !== null) {
                    return;
                }

                $file->update([
                    'due_date' => $dueDates->dueDateForType($type, $record)?->toDateString(),
                ]);
            });
    }

    public function down(): void
    {
        // Non-reversible data correction.
    }
};
