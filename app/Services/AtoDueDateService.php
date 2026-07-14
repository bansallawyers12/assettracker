<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\ComplianceDocumentType;
use App\Models\ComplianceYearRecord;
use App\Support\FinancialYear;
use Carbon\Carbon;

/**
 * Estimated ATO / ASIC lodgement due dates for entity-scope compliance slots.
 *
 * Self-lodge defaults apply unless the entity has uses_tax_agent set.
 * Exact dates can still shift for weekends/public holidays.
 */
class AtoDueDateService
{
    /**
     * Resolve an estimated due date for a seeded entity obligation type on a year record.
     */
    public function dueDateForType(ComplianceDocumentType $type, ComplianceYearRecord $record): ?Carbon
    {
        if ($record->asset_id !== null) {
            return null;
        }

        $record->loadMissing('businessEntity');
        $fyStart = $record->fy_start_date?->copy()->startOfDay();
        $fyEnd = $record->fy_end_date?->copy()->startOfDay();

        if ($fyStart === null || $fyEnd === null) {
            return null;
        }

        return $this->estimate($type->code, $fyStart, $fyEnd, $record->businessEntity);
    }

    /**
     * Estimate a due date from type code + FY bounds (no DB year record required).
     */
    public function estimate(string $code, Carbon $fyStart, Carbon $fyEnd, ?BusinessEntity $entity = null): ?Carbon
    {
        $fyStart = $fyStart->copy()->startOfDay();
        $fyEnd = $fyEnd->copy()->startOfDay();

        if ($entity !== null && ! $entity->complianceAppliesForFinancialYear($fyStart)) {
            return null;
        }

        $usesTaxAgent = $entity?->usesTaxAgent() ?? false;
        $taxReturnRequired = $entity?->requiresTaxReturn() ?? true;

        return match ($code) {
            'bas_q1' => $usesTaxAgent
                ? $this->fixedDate($fyStart->year, 11, 25)
                : $this->fixedDate($fyStart->year, 10, 28),
            'bas_q2' => $this->fixedDate($fyStart->year + 1, 2, 28),
            'bas_q3' => $usesTaxAgent
                ? $this->fixedDate($fyStart->year + 1, 5, 26)
                : $this->fixedDate($fyStart->year + 1, 4, 28),
            'bas_q4' => $usesTaxAgent
                ? $this->fixedDate($fyStart->year + 1, 8, 25)
                : $this->fixedDate($fyStart->year + 1, 7, 28),
            'itr', 'annual_accounts' => $usesTaxAgent
                ? $this->incomeTaxAgentDueDate($fyEnd)
                : $this->incomeTaxSelfLodgeDueDate($fyEnd),
            'bas_annual' => $this->annualBasDueDate($fyEnd, $taxReturnRequired, $usesTaxAgent),
            'asic_statement' => $this->asicDueDate($entity, $fyStart, $fyEnd),
            default => null,
        };
    }

    /**
     * Self-lodge default: 31 October in the calendar year the FY ends.
     */
    public function incomeTaxSelfLodgeDueDate(Carbon $fyEnd): Carbon
    {
        return $this->fixedDate($fyEnd->year, 10, 31);
    }

    /**
     * Tax-agent concession (typical): 15 May of the calendar year after FY end.
     */
    public function incomeTaxAgentDueDate(Carbon $fyEnd): Carbon
    {
        return $this->fixedDate($fyEnd->year + 1, 5, 15);
    }

    /**
     * Annual GST/BAS: aligns with tax return when required; otherwise 28 February after FY end.
     */
    public function annualBasDueDate(
        Carbon $fyEnd,
        bool $taxReturnRequired = true,
        bool $usesTaxAgent = false,
    ): Carbon {
        if ($taxReturnRequired) {
            return $usesTaxAgent
                ? $this->incomeTaxAgentDueDate($fyEnd)
                : $this->incomeTaxSelfLodgeDueDate($fyEnd);
        }

        return $this->fixedDate($fyEnd->year + 1, 2, 28);
    }

    /**
     * ASIC annual review anniversary falling within the financial year.
     */
    public function asicDueDate(?BusinessEntity $entity, Carbon $fyStart, Carbon $fyEnd): ?Carbon
    {
        if ($entity?->asic_renewal_date === null) {
            return null;
        }

        $renewal = Carbon::parse($entity->asic_renewal_date)->startOfDay();
        $month = (int) $renewal->month;
        $day = (int) $renewal->day;

        foreach ([$fyStart->year, $fyStart->year + 1] as $year) {
            $candidate = $this->fixedDate($year, $month, $day);
            if ($candidate->betweenIncluded($fyStart, $fyEnd)) {
                return $candidate;
            }
        }

        return null;
    }

    public function fyEndForStart(Carbon $fyStart): Carbon
    {
        return FinancialYear::forDate($fyStart)['end']->copy()->startOfDay();
    }

    private function fixedDate(int $year, int $month, int $day): Carbon
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        return Carbon::create($year, $month, min($day, $daysInMonth))->startOfDay();
    }
}
