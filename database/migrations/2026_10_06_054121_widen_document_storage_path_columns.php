<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE documents ALTER COLUMN path TYPE varchar(1024)');
            DB::statement('ALTER TABLE documents ALTER COLUMN path DROP NOT NULL');

            if (Schema::hasColumn('transactions', 'receipt_path')) {
                DB::statement('ALTER TABLE transactions ALTER COLUMN receipt_path TYPE varchar(1024)');
            }

            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->string('path', 1024)->nullable()->change();
        });

        if (Schema::hasColumn('transactions', 'receipt_path')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('receipt_path', 1024)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE documents ALTER COLUMN path TYPE varchar(255)');
            DB::statement('ALTER TABLE documents ALTER COLUMN path DROP NOT NULL');

            if (Schema::hasColumn('transactions', 'receipt_path')) {
                DB::statement('ALTER TABLE transactions ALTER COLUMN receipt_path TYPE varchar(255)');
            }

            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->string('path', 255)->nullable()->change();
        });

        if (Schema::hasColumn('transactions', 'receipt_path')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('receipt_path', 255)->nullable()->change();
            });
        }
    }
};
