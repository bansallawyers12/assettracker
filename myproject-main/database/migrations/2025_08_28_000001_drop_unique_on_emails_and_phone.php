<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            // registered_email should not be unique
            // Drop index by its name if known; otherwise, use dropUnique with column array
            $table->dropUnique(['registered_email']);
        });

        Schema::table('persons', function (Blueprint $table) {
            // persons.email should not be unique
            // In some schemas it may be nullable unique; dropping by column array works
            $table->dropUnique(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            $table->unique('registered_email');
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};


