<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('path', 1024)->change();
        });

        if (Schema::hasColumn('transactions', 'receipt_path')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('receipt_path', 1024)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('path', 255)->change();
        });

        if (Schema::hasColumn('transactions', 'receipt_path')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('receipt_path', 255)->nullable()->change();
            });
        }
    }
};
