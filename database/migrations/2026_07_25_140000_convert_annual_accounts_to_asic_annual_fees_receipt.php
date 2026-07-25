<?php

use App\Models\ComplianceCategory;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceDocumentType;
use App\Services\AtoDueDateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_CODE = 'annual_accounts';

    private const NEW_CODE = 'asic_annual_fees_receipt';

    private const NEW_LABEL = 'ASIC Annual Fees Receipt';

    private const NEW_CATEGORY = 'ASIC & Company';

    public function up(): void
    {
        $type = ComplianceDocumentType::query()->where('code', self::OLD_CODE)->first();

        if ($type === null) {
            return;
        }

        DB::table('compliance_document_types')
            ->where('id', $type->id)
            ->update([
                'code' => self::NEW_CODE,
                'label' => self::NEW_LABEL,
                'category_group' => self::NEW_CATEGORY,
                'sort_order' => 45,
            ]);

        // Refresh so due-date estimation uses the new code, not the stale in-memory model.
        $type->refresh();

        $dueDates = new AtoDueDateService;

        ComplianceDocumentFile::query()
            ->where('compliance_document_type_id', $type->id)
            ->with(['yearRecord.businessEntity'])
            ->orderBy('id')
            ->each(function (ComplianceDocumentFile $file) use ($dueDates, $type): void {
                $record = $file->yearRecord;
                if ($record === null) {
                    return;
                }

                $category = ComplianceCategory::query()->firstOrCreate(
                    [
                        'compliance_year_record_id' => $record->id,
                        'title' => self::NEW_CATEGORY,
                    ],
                    [
                        'sort_order' => 20,
                        'is_system' => true,
                    ]
                );

                $updates = [
                    'compliance_category_id' => $category->id,
                    // Recalculate from ASIC anniversary (clears stale ATO Oct 31 dates when unset).
                    'due_date' => $dueDates->dueDateForType($type, $record)?->toDateString(),
                ];

                if (! $file->custom_label) {
                    $updates['checklist_label'] = self::NEW_LABEL;
                }

                $file->update($updates);
            });
    }

    public function down(): void
    {
        $type = ComplianceDocumentType::query()->where('code', self::NEW_CODE)->first();

        if ($type === null) {
            return;
        }

        DB::table('compliance_document_types')
            ->where('id', $type->id)
            ->update([
                'code' => self::OLD_CODE,
                'label' => 'Annual Accounts',
                'category_group' => 'Tax & ATO',
                'sort_order' => 20,
            ]);

        ComplianceDocumentFile::query()
            ->where('compliance_document_type_id', $type->id)
            ->with('yearRecord')
            ->orderBy('id')
            ->each(function (ComplianceDocumentFile $file): void {
                $record = $file->yearRecord;
                if ($record === null) {
                    return;
                }

                $category = ComplianceCategory::query()->firstOrCreate(
                    [
                        'compliance_year_record_id' => $record->id,
                        'title' => 'Tax & ATO',
                    ],
                    [
                        'sort_order' => 10,
                        'is_system' => true,
                    ]
                );

                $file->update([
                    'compliance_category_id' => $category->id,
                    'checklist_label' => 'Annual Accounts',
                ]);
            });
    }
};
