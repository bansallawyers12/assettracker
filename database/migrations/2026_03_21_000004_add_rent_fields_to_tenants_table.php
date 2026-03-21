<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'rent_amount')) {
                $table->decimal('rent_amount', 12, 2)->nullable()->after('lease_expiry_reminder_days');
            }

            if (!Schema::hasColumn('tenants', 'rent_frequency')) {
                $table->string('rent_frequency', 20)->nullable()->after('rent_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'rent_frequency')) {
                $table->dropColumn('rent_frequency');
            }

            if (Schema::hasColumn('tenants', 'rent_amount')) {
                $table->dropColumn('rent_amount');
            }
        });
    }
};
