<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('subject_to_bas')->default(false)->index();
            $table->boolean('is_flagged')->default(false)->index();
            $table->text('comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['comments', 'subject_to_bas', 'is_flagged']);
        });
    }
};
