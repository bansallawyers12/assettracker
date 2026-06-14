<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;

class CarReportService
{
    /**
     * Build the fleet register dataset.
     *
     * @param  array<int>|null  $entityIds  null = all reporting entities
     */
    public function fleetRegister(?array $entityIds): array
    {
        $query = Asset::where('asset_type', 'Car')
            ->with('businessEntity')
            ->orderBy('name');

        if ($entityIds !== null && $entityIds !== []) {
            $query->whereIn('business_entity_id', $entityIds);
        }

        $cars = $query->get();

        $now = Carbon::now()->startOfDay();
        $in30 = Carbon::now()->addDays(30)->endOfDay();

        $regoOverdue = $cars->filter(
            fn (Asset $c) => $c->registration_due_date && $c->registration_due_date->lt($now)
        )->count();

        $regoDueSoon = $cars->filter(
            fn (Asset $c) => $c->registration_due_date
                && $c->registration_due_date->gte($now)
                && $c->registration_due_date->lte($in30)
        )->count();

        $insuranceOverdue = $cars->filter(
            fn (Asset $c) => $c->insurance_due_date && $c->insurance_due_date->lt($now)
        )->count();

        $insuranceDueSoon = $cars->filter(
            fn (Asset $c) => $c->insurance_due_date
                && $c->insurance_due_date->gte($now)
                && $c->insurance_due_date->lte($in30)
        )->count();

        $serviceDueSoon = $cars->filter(
            fn (Asset $c) => $c->service_due_date
                && $c->service_due_date->gte($now)
                && $c->service_due_date->lte($in30)
        )->count();

        $serviceOverdue = $cars->filter(
            fn (Asset $c) => $c->service_due_date && $c->service_due_date->lt($now)
        )->count();

        return [
            'cars' => $cars,
            'totals' => [
                'total_cars'         => $cars->count(),
                'rego_overdue'       => $regoOverdue,
                'rego_due_soon'      => $regoDueSoon,
                'insurance_overdue'  => $insuranceOverdue,
                'insurance_due_soon' => $insuranceDueSoon,
                'service_overdue'    => $serviceOverdue,
                'service_due_soon'   => $serviceDueSoon,
            ],
        ];
    }
}
