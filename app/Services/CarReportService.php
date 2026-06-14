<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CarReportService
{
    /**
     * Build the Car Register dataset.
     *
     * @param  array<int>|null  $entityIds  null = all operational reporting entities
     * @return array{cars: Collection<int, Asset>, totals: array<string, int>}
     */
    public function carRegister(?array $entityIds = null): array
    {
        $query = Asset::query()
            ->where('asset_type', 'Car')
            ->whereHas('businessEntity', fn ($q) => $q->operationalEntities())
            ->with(['businessEntity', 'user']);

        if ($entityIds !== null && $entityIds !== []) {
            $query->whereIn('business_entity_id', $entityIds);
        }

        $cars = $query->get()->sortBy([
            fn (Asset $car) => strtolower($car->businessEntity?->legal_name ?? ''),
            fn (Asset $car) => strtolower($car->name),
        ])->values();

        $now = Carbon::now()->startOfDay();
        $in30 = Carbon::now()->addDays(30)->endOfDay();

        return [
            'cars' => $cars,
            'totals' => [
                'total_cars'         => $cars->count(),
                'rego_overdue'       => $this->countDueBefore($cars, 'registration_due_date', $now),
                'rego_due_soon'      => $this->countDueBetween($cars, 'registration_due_date', $now, $in30),
                'insurance_overdue'  => $this->countDueBefore($cars, 'insurance_due_date', $now),
                'insurance_due_soon' => $this->countDueBetween($cars, 'insurance_due_date', $now, $in30),
                'service_overdue'    => $this->countDueBefore($cars, 'service_due_date', $now),
                'service_due_soon'   => $this->countDueBetween($cars, 'service_due_date', $now, $in30),
            ],
        ];
    }

    /**
     * @param  Collection<int, Asset>  $cars
     */
    private function countDueBefore(Collection $cars, string $field, Carbon $before): int
    {
        return $cars->filter(
            fn (Asset $car) => $car->{$field} && $car->{$field}->lt($before)
        )->count();
    }

    /**
     * @param  Collection<int, Asset>  $cars
     */
    private function countDueBetween(Collection $cars, string $field, Carbon $from, Carbon $to): int
    {
        return $cars->filter(
            fn (Asset $car) => $car->{$field}
                && $car->{$field}->gte($from)
                && $car->{$field}->lte($to)
        )->count();
    }
}
