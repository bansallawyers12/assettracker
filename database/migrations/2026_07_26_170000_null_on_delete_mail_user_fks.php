<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prevent user hard-delete from leaving dangling mailbox ownership without an FK.
 * Attribution becomes nullable (nullOnDelete) as a DB safety net; app guards still block.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('mail_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        if (Schema::hasTable('emails') && Schema::hasColumn('emails', 'user_id') && ! $this->hasUserForeignKey('emails')) {
            Schema::table('emails', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::table('mail_messages')->whereNull('user_id')->exists()
            || DB::table('mail_labels')->whereNull('user_id')->exists()) {
            throw new \RuntimeException(
                'Cannot rollback mail user_id nullability while mail_messages.user_id or mail_labels.user_id contain NULL values.'
            );
        }

        Schema::table('mail_messages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('mail_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('mail_labels', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('mail_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        if (Schema::hasTable('emails') && $this->hasUserForeignKey('emails')) {
            Schema::table('emails', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }
    }

    private function hasUserForeignKey(string $table): bool
    {
        return collect(DB::select(
            "SELECT 1
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
             WHERE tc.constraint_type = 'FOREIGN KEY'
               AND tc.table_schema = current_schema()
               AND tc.table_name = ?
               AND kcu.column_name = 'user_id'",
            [$table]
        ))->isNotEmpty();
    }
};
