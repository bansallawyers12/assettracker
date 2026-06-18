<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['business_entity_id']);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_entity_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->after('business_entity_id')->constrained()->nullOnDelete();
            $table->enum('account_purpose', ['general', 'loan', 'loan_repayment', 'offset'])
                ->default('general')
                ->after('account_number');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreign('business_entity_id')
                ->references('id')
                ->on('business_entities')
                ->cascadeOnDelete();
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->renameColumn('nickname', 'account_name');
        });

        DB::table('bank_accounts')
            ->whereNull('account_name')
            ->update(['account_name' => DB::raw('bank_name')]);

        $accounts = DB::table('bank_accounts')
            ->whereNotNull('business_entity_id')
            ->whereNull('user_id')
            ->get(['id', 'business_entity_id']);

        foreach ($accounts as $account) {
            $userId = DB::table('business_entities')
                ->where('id', $account->business_entity_id)
                ->value('user_id');

            if ($userId) {
                DB::table('bank_accounts')
                    ->where('id', $account->id)
                    ->update(['user_id' => $userId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->renameColumn('account_name', 'nickname');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['business_entity_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'account_purpose']);
            $table->unsignedBigInteger('business_entity_id')->nullable(false)->change();
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreign('business_entity_id')
                ->references('id')
                ->on('business_entities')
                ->cascadeOnDelete();
        });
    }
};
