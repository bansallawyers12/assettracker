<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Asset extends Model
{
    /**
     * Asset types that typically have tenants / rent invoices.
     */
    public const LEASABLE_ASSET_TYPES = [
        'House Owned',
        'House Rented',
        'Warehouse',
        'Land',
        'Office',
        'Shop',
        'Real Estate',
        'Suite',
    ];

    /**
     * Property asset types (non-car) that support loan & banking fields.
     */
    public const PROPERTY_ASSET_TYPES = [
        'House Owned',
        'House Rented',
        'Warehouse',
        'Land',
        'Office',
        'Shop',
        'Real Estate',
        'Suite',
    ];

    public const DUE_DATE_REMINDERS = [
        ['label' => 'Registration', 'field' => 'registration_due_date', 'color' => 'red', 'finalize_type' => 'registration'],
        ['label' => 'Insurance', 'field' => 'insurance_due_date', 'color' => 'orange', 'finalize_type' => 'insurance'],
        ['label' => 'Service', 'field' => 'service_due_date', 'color' => 'blue', 'finalize_type' => 'service'],
        ['label' => 'Council Rates', 'field' => 'council_rates_due_date', 'color' => 'purple', 'finalize_type' => 'council_rates'],
        ['label' => 'Owners Corp', 'field' => 'owners_corp_due_date', 'color' => 'green', 'finalize_type' => 'owners_corp'],
        ['label' => 'Land Tax', 'field' => 'land_tax_due_date', 'color' => 'yellow', 'finalize_type' => 'land_tax'],
    ];

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
        'loan_provider',
        'loan_interest_rate',
        'loan_payment_amount',
        'loan_payment_frequency',
        'loan_balance',
        'equity_required',
        'direct_debit_amount',
        'rent_paid_by',
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
        'loan_interest_rate' => 'decimal:4',
        'loan_payment_amount' => 'decimal:2',
        'loan_balance' => 'decimal:2',
        'equity_required' => 'decimal:2',
        'direct_debit_amount' => 'decimal:2',
    ];

    public function isPropertyType(): bool
    {
        return in_array($this->asset_type, self::PROPERTY_ASSET_TYPES, true);
    }

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
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

    public function documentCategories()
    {
        return $this->hasMany(DocumentCategory::class, 'asset_id');
    }

    public function bankAccounts()
    {
        return $this->belongsToMany(BankAccount::class, 'asset_bank_account')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function bankAccountForRole(string $role): ?BankAccount
    {
        if ($this->relationLoaded('bankAccounts')) {
            return $this->bankAccounts->first(fn (BankAccount $account) => $account->pivot->role === $role);
        }

        return $this->bankAccounts()->wherePivot('role', $role)->first();
    }

    /**
     * Linked loan account for this asset (loan + lender BSB/account).
     * Falls back to legacy loan_repayment pivot links until migrated.
     */
    public function linkedLoanAccount(): ?BankAccount
    {
        return $this->bankAccountForRole(BankAccount::ROLE_LOAN)
            ?? $this->bankAccountForRole(BankAccount::ROLE_LOAN_REPAYMENT);
    }

    public static $depreciationMethods = [
        'straight_line' => 'Straight Line',
        'reducing_balance' => 'Reducing Balance',
        'units_of_production' => 'Units of Production',
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
        if (! $this->is_depreciable || ! $this->useful_life_years) {
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
        if (! $this->is_depreciable || ! $this->useful_life_years) {
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

    /**
     * @return list<string>
     */
    public static function dueDateFieldNames(): array
    {
        return array_column(self::DUE_DATE_REMINDERS, 'field');
    }

    /**
     * Due dates that are overdue or fall within the next N days (inclusive).
     */
    public function dueDateReminderItems(int $withinDays = 15): Collection
    {
        $cutoff = now()->startOfDay()->addDays($withinDays);

        return collect(self::DUE_DATE_REMINDERS)
            ->map(function (array $def) {
                $date = $this->{$def['field']};

                return array_merge($def, [
                    'date' => $date ? Carbon::parse($date)->startOfDay() : null,
                ]);
            })
            ->filter(fn (array $item) => $item['date'] !== null && $item['date']->lte($cutoff))
            ->sortBy('date')
            ->values();
    }

    /**
     * Flat list of upcoming asset due-date rows for dashboard and reports.
     *
     * @param  list<int>|null  $entityIds
     */
    public static function upcomingDueDateRows(int $withinDays = 15, ?array $entityIds = null, bool $includeInactive = false): Collection
    {
        $query = self::dueWithinDays($withinDays, $includeInactive)->with('businessEntity');

        if ($entityIds !== null) {
            $query->whereIn('business_entity_id', $entityIds);
        }

        return $query->get()->flatMap(function (self $asset) use ($withinDays) {
            return $asset->dueDateReminderItems($withinDays)->map(fn (array $item) => (object) [
                'asset' => $asset,
                'label' => $item['label'],
                'date' => $item['date'],
                'color' => $item['color'],
                'finalize_type' => $item['finalize_type'],
            ]);
        })->sortBy('date')->values();
    }

    public static function dueWithinDays(int $days = 15, bool $includeInactive = false): Builder
    {
        $query = self::whereNotNull('business_entity_id');

        if (! $includeInactive) {
            $query->where('status', 'Active');
        }

        $cutoff = now()->startOfDay()->addDays($days);

        return $query->where(function ($query) use ($cutoff) {
            foreach (self::dueDateFieldNames() as $field) {
                $query->orWhere(function ($q) use ($field, $cutoff) {
                    $q->whereNotNull($field)
                        ->whereDate($field, '<=', $cutoff);
                });
            }
        });
    }

    public static function dueWithin15Days($includeInactive = false): Builder
    {
        return self::dueWithinDays(15, $includeInactive);
    }
}
