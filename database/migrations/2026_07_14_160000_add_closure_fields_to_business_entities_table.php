<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            $table->date('closed_date')->nullable()->after('status');
            $table->text('closed_reason')->nullable()->after('closed_date');
        });
    }

    public function down(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            $table->dropColumn(['closed_date', 'closed_reason']);
        });
    }
};
