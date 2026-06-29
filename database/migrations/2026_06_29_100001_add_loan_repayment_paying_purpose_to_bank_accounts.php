<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PURPOSES = [
        'general',
        'loan',
        'loan_repayment',
        'loan_repayment_paying',
        'offset',
        'rent_receiving',
        'rent_paying',
    ];

    public function up(): void
    {
        $this->alterAccountPurposeConstraint(self::PURPOSES);
    }

    public function down(): void
    {
        DB::table('bank_accounts')
            ->where('account_purpose', 'loan_repayment_paying')
            ->update(['account_purpose' => 'general']);

        $this->alterAccountPurposeConstraint([
            'general',
            'loan',
            'loan_repayment',
            'offset',
            'rent_receiving',
            'rent_paying',
        ]);
    }

    /**
     * @param  list<string>  $purposes
     */
    private function alterAccountPurposeConstraint(array $purposes): void
    {
        $driver = DB::connection()->getDriverName();
        $enumValues = "'".implode("', '", $purposes)."'";

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE bank_accounts MODIFY COLUMN account_purpose ENUM({$enumValues}) NOT NULL DEFAULT 'general'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('
                ALTER TABLE bank_accounts
                DROP CONSTRAINT IF EXISTS bank_accounts_account_purpose_check
            ');

            DB::statement("
                ALTER TABLE bank_accounts
                ADD CONSTRAINT bank_accounts_account_purpose_check
                CHECK (account_purpose IN ({$enumValues}))
            ");
        }
    }
};
