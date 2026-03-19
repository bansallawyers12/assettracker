<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The User model encrypts the email field before storing (EncryptsAttributes trait).
     * Laravel's Crypt::encrypt() produces base64 output that exceeds VARCHAR(255).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('email')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->change();
        });
    }
};
