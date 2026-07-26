<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stop hard-deleting a user from cascading into shared portfolio and ledger data.
 *
 * - business_entities / real_estate_companies: restrict (must reassign or keep the user)
 * - journal_entries / notes / reminders: null attribution instead of wiping rows
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        if (Schema::hasTable('real_estate_companies')) {
            Schema::table('real_estate_companies', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Cannot restore NOT NULL + cascade if attribution was cleared by nullOnDelete.
        if (DB::table('journal_entries')->whereNull('created_by')->exists()
            || DB::table('notes')->whereNull('user_id')->exists()
            || DB::table('reminders')->whereNull('user_id')->exists()) {
            throw new \RuntimeException(
                'Cannot rollback prevent_user_delete_cascading_portfolio_data while journal_entries.created_by, notes.user_id, or reminders.user_id contain NULL values.'
            );
        }

        Schema::table('business_entities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        if (Schema::hasTable('real_estate_companies')) {
            Schema::table('real_estate_companies', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }
};
