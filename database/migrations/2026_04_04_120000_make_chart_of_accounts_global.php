<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One shared chart of accounts for the whole application (no per-entity rows).
     * Merges duplicate account_code rows across entities into a single row and rewires FKs.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('chart_of_accounts', 'business_entity_id')) {
            return;
        }

        DB::transaction(function () {
            $codes = DB::table('chart_of_accounts')->distinct()->pluck('account_code');

            foreach ($codes as $code) {
                $ids = DB::table('chart_of_accounts')->where('account_code', $code)->orderBy('id')->pluck('id');
                if ($ids->count() <= 1) {
                    continue;
                }

                $keepId = $ids->first();
                $removeIds = $ids->slice(1)->values()->all();

                foreach ($removeIds as $oldId) {
                    DB::table('journal_lines')->where('chart_of_account_id', $oldId)->update(['chart_of_account_id' => $keepId]);
                    DB::table('assets')->where('depreciation_account_id', $oldId)->update(['depreciation_account_id' => $keepId]);
                    DB::table('chart_of_accounts')->where('parent_account_id', $oldId)->update(['parent_account_id' => $keepId]);
                }

                DB::table('chart_of_accounts')->whereIn('id', $removeIds)->delete();
            }

            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropUnique('coa_entity_code_unique');
            });

            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropForeign(['business_entity_id']);
            });

            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropIndex(['business_entity_id', 'account_type']);
                $table->dropIndex(['business_entity_id', 'account_category']);
            });

            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropColumn('business_entity_id');
                $table->unique('account_code');
            });
        });
    }

    /**
     * Cannot reliably restore per-entity rows after merge.
     */
    public function down(): void
    {
        //
    }
};
