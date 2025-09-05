<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'business_entity_id',
        'user_id',
        'asset_type',
        'name',
        'acquisition_date',
        'acquisition_cost',
        'current_value',
        'status',
        'description',
        'registration_number',
        'registration_due_date',
        'insurance_company',
        'insurance_due_date',
        'insurance_amount',
        'vin_number',
        'mileage',
        'fuel_type',
        'service_due_date',
        'vic_roads_updated',
        'address',
        'square_footage',
        'council_rates_amount',
        'council_rates_due_date',
        'owners_corp_amount',
        'owners_corp_due_date',
        'land_tax_amount',
        'land_tax_due_date',
        'sro_updated',
        'real_estate_percentage',
        'rental_income',
        'depreciation_method',
        'useful_life_years',
        'residual_value',
        'accumulated_depreciation',
        'book_value',
        'is_depreciable',
        'depreciation_account_id',
        'disposal_date',
        'disposal_amount',
    ];

    protected $casts = [
        'acquisition_date' => 'datetime',
        'registration_due_date' => 'datetime',
        'insurance_due_date' => 'datetime',
        'service_due_date' => 'datetime',
        'council_rates_due_date' => 'datetime',
        'owners_corp_due_date' => 'datetime',
        'land_tax_due_date' => 'datetime',
        'disposal_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'acquisition_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'council_rates_amount' => 'decimal:2',
        'owners_corp_amount' => 'decimal:2',
        'land_tax_amount' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'disposal_amount' => 'decimal:2',
        'vic_roads_updated' => 'boolean',
        'sro_updated' => 'boolean',
        'is_depreciable' => 'boolean',
        'mileage' => 'integer',
        'square_footage' => 'integer',
        'useful_life_years' => 'integer',
        'real_estate_percentage' => 'decimal:2',
        'rental_income' => 'decimal:2',
    ];

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assetTransactions()
    {
        return $this->hasMany(AssetTransaction::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function reminders()
    {
        return $this->morphMany(Reminder::class, 'reminder');
    }

    public function mailMessages()
    {
        return $this->belongsToMany(MailMessage::class, 'asset_mail_message');
    }

    public function depreciationAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_account_id');
    }

    public function depreciationSchedules()
    {
        return $this->hasMany(DepreciationSchedule::class);
    }

    public static $depreciationMethods = [
        'straight_line' => 'Straight Line',
        'reducing_balance' => 'Reducing Balance',
        'units_of_production' => 'Units of Production'
    ];

    public function calculateDepreciation($method = null, $asOfDate = null)
    {
        $method = $method ?? $this->depreciation_method;
        $asOfDate = $asOfDate ?? now();
        
        switch ($method) {
            case 'straight_line':
                return $this->calculateStraightLineDepreciation($asOfDate);
            case 'reducing_balance':
                return $this->calculateReducingBalanceDepreciation($asOfDate);
            default:
                return 0;
        }
    }

    private function calculateStraightLineDepreciation($asOfDate)
    {
        if (!$this->is_depreciable || !$this->useful_life_years) {
            return 0;
        }
        
        $monthsInService = $this->acquisition_date->diffInMonths($asOfDate);
        $totalMonths = $this->useful_life_years * 12;
        
        if ($monthsInService >= $totalMonths) {
            return $this->acquisition_cost - $this->residual_value;
        }
        
        $annualDepreciation = ($this->acquisition_cost - $this->residual_value) / $this->useful_life_years;
        return ($annualDepreciation / 12) * $monthsInService;
    }

    private function calculateReducingBalanceDepreciation($asOfDate)
    {
        if (!$this->is_depreciable || !$this->useful_life_years) {
            return 0;
        }
        
        $monthsInService = $this->acquisition_date->diffInMonths($asOfDate);
        $totalMonths = $this->useful_life_years * 12;
        
        if ($monthsInService >= $totalMonths) {
            return $this->acquisition_cost - $this->residual_value;
        }
        
        // Calculate reducing balance rate (typically 1.5x straight line rate)
        $straightLineRate = 1 / $this->useful_life_years;
        $reducingBalanceRate = $straightLineRate * 1.5;
        
        $depreciation = 0;
        $bookValue = $this->acquisition_cost;
        
        for ($month = 1; $month <= $monthsInService; $month++) {
            $monthlyDepreciation = $bookValue * ($reducingBalanceRate / 12);
            $depreciation += $monthlyDepreciation;
            $bookValue -= $monthlyDepreciation;
            
            // Ensure we don't depreciate below residual value
            if ($bookValue <= $this->residual_value) {
                $depreciation = $this->acquisition_cost - $this->residual_value;
                break;
            }
        }
        
        return $depreciation;
    }

    /**
     * Get all pending reminders for the asset.
     */
    public function pendingReminders()
    {
        return $this->reminders()->pending();
    }

    /**
     * Get all overdue reminders for the asset.
     */
    public function overdueReminders()
    {
        return $this->reminders()->overdue();
    }

    public static function dueWithin15Days($includeInactive = false)
    {
        $query = self::whereNotNull('business_entity_id');

        if (!$includeInactive) {
            $query->where('status', 'Active');
        }

        return $query->where(function ($query) {
            $startDate = now();
            $endDate = now()->addDays(15);
            $dateFields = [
                'registration_due_date',
                'insurance_due_date',
                'service_due_date',
                'council_rates_due_date',
                'owners_corp_due_date',
                'land_tax_due_date',
            ];

            foreach ($dateFields as $field) {
                $query->orWhere(function ($q) use ($field, $startDate, $endDate) {
                    $q->whereNotNull($field)
                      ->whereBetween($field, [$startDate, $endDate]);
                });
            }
            return $query;
        });
    }
}