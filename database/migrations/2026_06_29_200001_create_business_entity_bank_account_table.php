<?php

use App\Models\BankAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_entity_bank_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 64);
            $table->timestamps();

            $table->unique(['business_entity_id', 'bank_account_id', 'purpose'], 'be_ba_purpose_unique');
        });

        $now = now();

        foreach (DB::table('bank_accounts')->whereNotNull('business_entity_id')->orderBy('id')->get() as $row) {
            if (! in_array($row->account_purpose, BankAccount::ENTITY_PURPOSES, true)) {
                continue;
            }

            DB::table('business_entity_bank_account')->insertOrIgnore([
                'business_entity_id' => $row->business_entity_id,
                'bank_account_id' => $row->id,
                'purpose' => $row->account_purpose,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_entity_bank_account');
    }
};
