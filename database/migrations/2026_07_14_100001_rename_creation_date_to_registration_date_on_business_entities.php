<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('business_entities', 'creation_date')
            && ! Schema::hasColumn('business_entities', 'registration_date')) {
            Schema::table('business_entities', function (Blueprint $table) {
                $table->renameColumn('creation_date', 'registration_date');
            });

            return;
        }

        if (! Schema::hasColumn('business_entities', 'registration_date')) {
            Schema::table('business_entities', function (Blueprint $table) {
                $table->date('registration_date')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('business_entities', 'registration_date')
            && ! Schema::hasColumn('business_entities', 'creation_date')) {
            Schema::table('business_entities', function (Blueprint $table) {
                $table->renameColumn('registration_date', 'creation_date');
            });
        }
    }
};
