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
            if (!Schema::hasColumn('tenants', 'lease_duration_value')) {
                $table->unsignedInteger('lease_duration_value')->nullable()->after('move_in_date');
            }

            if (!Schema::hasColumn('tenants', 'lease_duration_unit')) {
                $table->string('lease_duration_unit', 20)->nullable()->after('lease_duration_value');
            }

            if (!Schema::hasColumn('tenants', 'lease_expiry_date')) {
                $table->date('lease_expiry_date')->nullable()->after('lease_duration_unit');
            }

            if (!Schema::hasColumn('tenants', 'lease_expiry_reminder_days')) {
                $table->unsignedInteger('lease_expiry_reminder_days')->nullable()->after('lease_expiry_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'lease_expiry_reminder_days')) {
                $table->dropColumn('lease_expiry_reminder_days');
            }

            if (Schema::hasColumn('tenants', 'lease_expiry_date')) {
                $table->dropColumn('lease_expiry_date');
            }

            if (Schema::hasColumn('tenants', 'lease_duration_unit')) {
                $table->dropColumn('lease_duration_unit');
            }

            if (Schema::hasColumn('tenants', 'lease_duration_value')) {
                $table->dropColumn('lease_duration_value');
            }
        });
    }
};
