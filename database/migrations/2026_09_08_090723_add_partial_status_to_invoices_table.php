<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->alterInvoiceStatusCheck([
            'draft',
            'approved',
            'partial',
            'paid',
            'void',
        ]);
    }

    public function down(): void
    {
        $this->alterInvoiceStatusCheck([
            'draft',
            'approved',
            'paid',
            'void',
        ]);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function alterInvoiceStatusCheck(array $statuses): void
    {
        $driver = DB::connection()->getDriverName();
        $enumValues = "'".implode("', '", $statuses)."'";

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM({$enumValues}) NOT NULL DEFAULT 'draft'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('
                ALTER TABLE invoices
                DROP CONSTRAINT IF EXISTS invoices_status_check
            ');

            DB::statement("
                ALTER TABLE invoices
                ADD CONSTRAINT invoices_status_check
                CHECK ((status)::text = ANY ((ARRAY[{$enumValues}])::text[]))
            ");
        }
    }
};
