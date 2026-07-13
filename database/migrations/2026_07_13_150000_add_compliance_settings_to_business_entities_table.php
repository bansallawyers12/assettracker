<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            $table->string('bas_reporting_frequency', 20)->nullable()->after('asic_renewal_date');
            $table->boolean('uses_tax_agent')->default(false)->after('bas_reporting_frequency');
            $table->boolean('gst_registered')->default(true)->after('uses_tax_agent');
            $table->boolean('entity_tax_return_required')->default(true)->after('gst_registered');
        });
    }

    public function down(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            $table->dropColumn([
                'bas_reporting_frequency',
                'uses_tax_agent',
                'gst_registered',
                'entity_tax_return_required',
            ]);
        });
    }
};
