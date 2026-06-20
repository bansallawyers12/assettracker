<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\ComplianceDocumentType;
use Illuminate\Database\Seeder;

class ComplianceDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $propertyTypes = Asset::PROPERTY_ASSET_TYPES;

        $types = [
            ['code' => 'itr', 'label' => 'Income Tax Return', 'scope' => 'entity', 'frequency' => 'annual', 'sort_order' => 10],
            ['code' => 'annual_accounts', 'label' => 'Annual Accounts', 'scope' => 'entity', 'frequency' => 'annual', 'sort_order' => 20],
            ['code' => 'bas_annual', 'label' => 'BAS (Annual summary)', 'scope' => 'entity', 'frequency' => 'annual', 'sort_order' => 30],
            ['code' => 'bas_q1', 'label' => 'BAS Q1 (Jul–Sep)', 'scope' => 'entity', 'frequency' => 'quarterly', 'sort_order' => 31],
            ['code' => 'bas_q2', 'label' => 'BAS Q2 (Oct–Dec)', 'scope' => 'entity', 'frequency' => 'quarterly', 'sort_order' => 32],
            ['code' => 'bas_q3', 'label' => 'BAS Q3 (Jan–Mar)', 'scope' => 'entity', 'frequency' => 'quarterly', 'sort_order' => 33],
            ['code' => 'bas_q4', 'label' => 'BAS Q4 (Apr–Jun)', 'scope' => 'entity', 'frequency' => 'quarterly', 'sort_order' => 34],
            ['code' => 'asic_statement', 'label' => 'ASIC Annual Statement', 'scope' => 'entity', 'frequency' => 'annual', 'sort_order' => 40],
            ['code' => 'other_entity', 'label' => 'Other', 'scope' => 'entity', 'frequency' => 'ad_hoc', 'sort_order' => 99, 'is_required' => false],
            ['code' => 'land_tax', 'label' => 'Land Tax', 'scope' => 'asset', 'frequency' => 'annual', 'sort_order' => 10, 'asset_types' => $propertyTypes],
            ['code' => 'council_rates', 'label' => 'Council Rates', 'scope' => 'asset', 'frequency' => 'annual', 'sort_order' => 20, 'asset_types' => $propertyTypes],
            ['code' => 'water_rates', 'label' => 'Water Rates', 'scope' => 'asset', 'frequency' => 'annual', 'sort_order' => 30, 'asset_types' => $propertyTypes],
            ['code' => 'insurance_certificate', 'label' => 'Insurance Certificate', 'scope' => 'asset', 'frequency' => 'annual', 'sort_order' => 40, 'asset_types' => null],
            ['code' => 'depreciation_schedule', 'label' => 'Depreciation Schedule', 'scope' => 'asset', 'frequency' => 'annual', 'sort_order' => 50, 'asset_types' => $propertyTypes],
            ['code' => 'other_asset', 'label' => 'Other', 'scope' => 'asset', 'frequency' => 'ad_hoc', 'sort_order' => 99, 'is_required' => false, 'asset_types' => null],
        ];

        foreach ($types as $row) {
            ComplianceDocumentType::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'label'       => $row['label'],
                    'scope'       => $row['scope'],
                    'frequency'   => $row['frequency'],
                    'asset_types' => $row['asset_types'] ?? null,
                    'sort_order'  => $row['sort_order'],
                    'is_required' => $row['is_required'] ?? true,
                    'is_active'   => true,
                ]
            );
        }
    }
}
