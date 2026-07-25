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
        $oldType = ComplianceDocumentType::query()->where('code', self::OLD_CODE)->first();
        $newType = ComplianceDocumentType::query()->where('code', self::NEW_CODE)->first();

        if ($oldType === null && $newType === null) {
            return;
        }

        // Seed may already have created NEW_CODE while OLD_CODE still exists.
        if ($oldType !== null && $newType !== null && $oldType->id !== $newType->id) {
            ComplianceDocumentFile::query()
                ->where('compliance_document_type_id', $oldType->id)
                ->update(['compliance_document_type_id' => $newType->id]);

            $oldType->delete();
            $targetType = $newType;
        } elseif ($oldType !== null) {
            DB::table('compliance_document_types')
                ->where('id', $oldType->id)
                ->update([
                    'code' => self::NEW_CODE,
                    'label' => self::NEW_LABEL,
                    'category_group' => self::NEW_CATEGORY,
                    'sort_order' => 45,
                ]);

            $targetType = ComplianceDocumentType::query()->find($oldType->id);
        } else {
            $targetType = $newType;
        }

        if ($targetType === null) {
            return;
        }

        // Keep metadata in sync even when the type already had the new code.
        DB::table('compliance_document_types')
            ->where('id', $targetType->id)
            ->update([
                'code' => self::NEW_CODE,
                'label' => self::NEW_LABEL,
                'category_group' => self::NEW_CATEGORY,
                'sort_order' => 45,
            ]);

        $targetType->refresh();
        $dueDates = new AtoDueDateService;

        ComplianceDocumentFile::query()
            ->where('compliance_document_type_id', $targetType->id)
            ->with(['yearRecord.businessEntity', 'type'])
            ->orderBy('id')
            ->each(function (ComplianceDocumentFile $file) use ($dueDates, $targetType): void {
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
                    'checklist_label' => self::NEW_LABEL,
                ];

                $file->setRelation('type', $targetType);
                $estimatedDue = $dueDates->dueDateForType($targetType, $record);
                if ($estimatedDue !== null) {
                    $updates['due_date'] = $estimatedDue->toDateString();
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
