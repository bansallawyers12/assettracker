<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->enum('holder_type', ['entity', 'person', 'other'])
                ->nullable()
                ->after('account_purpose');

            $table->foreignId('holder_entity_id')
                ->nullable()
                ->after('holder_type')
                ->constrained('business_entities')
                ->nullOnDelete();

            $table->foreignId('holder_person_id')
                ->nullable()
                ->after('holder_entity_id')
                ->constrained('persons')
                ->nullOnDelete();

            $table->string('holder_other')->nullable()->after('holder_person_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['holder_entity_id']);
            $table->dropForeign(['holder_person_id']);
            $table->dropColumn(['holder_type', 'holder_entity_id', 'holder_person_id', 'holder_other']);
        });
    }
};
