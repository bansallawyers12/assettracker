<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add email_hash column for deterministic encrypted-email lookups
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_hash', 64)->nullable()->index()->after('email');
        });

        // 2. Widen two_factor_secret from VARCHAR(255) to TEXT (encrypted value exceeds 255 chars)
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->change();
        });

        // 3. Backfill email_hash for existing users (decrypt stored email → compute HMAC)
        DB::table('users')->orderBy('id')->each(function ($user) {
            if (empty($user->email)) return;

            try {
                $plainEmail = Crypt::decrypt($user->email);
            } catch (\Exception $e) {
                // Email may already be plaintext (pre-encryption rows)
                $plainEmail = $user->email;
            }

            $hash = hash_hmac('sha256', strtolower(trim($plainEmail)), config('app.key'));

            DB::table('users')->where('id', $user->id)->update(['email_hash' => $hash]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_hash');
            $table->string('two_factor_secret')->nullable()->change();
        });
    }
};
