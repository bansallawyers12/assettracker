<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceDocumentType;
use App\Models\ComplianceYearRecord;
use App\Support\FinancialYear;
use Carbon\Carbon;

class ComplianceReportService
{
    /**
     * @param  array<int>|null  $entityIds  null = all reporting entities
     * @return array{
     *     fy_label: string,
     *     fy_start: string,
     *     total_entities: int,
     *     missing_itr: int,
     *     rows: list<array{entity_id: int, entity_name: string, fy_label: string, fy_start: string, compliance_url: string}>
     * }
     */
    public function missingItrReport(?array $entityIds = null, Carbon|string|null $fyStart = null): array
    {
        $fyStart = FinancialYear::forDate(
            $fyStart instanceof Carbon ? $fyStart : ($fyStart ? Carbon::parse($fyStart) : FinancialYear::currentStart())
        )['start'];

        $itrType = ComplianceDocumentType::query()->where('code', 'itr')->first();

        $entitiesQuery = BusinessEntity::query()
            ->forFinancialReports()
            ->orderBy('legal_name');

        if ($entityIds !== null && $entityIds !== []) {
            $entitiesQuery->whereIn('id', $entityIds);
        }

        $entities = $entitiesQuery->get();
        $rows = [];

        foreach ($entities as $entity) {
            $record = ComplianceYearRecord::query()
                ->where('business_entity_id', $entity->id)
                ->whereNull('asset_id')
                ->whereDate('fy_start_date', $fyStart->toDateString())
                ->first();

            $hasItr = false;
            if ($record && $itrType) {
                $hasItr = ComplianceDocumentFile::query()
                    ->where('compliance_year_record_id', $record->id)
                    ->where('compliance_document_type_id', $itrType->id)
                    ->whereNotNull('path')
                    ->exists();
            }

            if (! $hasItr) {
                $rows[] = [
                    'entity_id' => $entity->id,
                    'entity_name' => $entity->legal_name,
                    'fy_label' => FinancialYear::label($fyStart),
                    'fy_start' => $fyStart->toDateString(),
                    'compliance_url' => route('business-entities.show', $entity->id)
                        .'?fy_start='.$fyStart->toDateString()
                        .'#tab_compliance',
                ];
            }
        }

        return [
            'fy_label' => FinancialYear::label($fyStart),
            'fy_start' => $fyStart->toDateString(),
            'total_entities' => $entities->count(),
            'missing_itr' => count($rows),
            'rows' => $rows,
        ];
    }
}
