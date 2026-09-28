<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speed up inbox list queries: WHERE user_id = ? ORDER BY sent_date DESC.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                CREATE INDEX mail_messages_user_sent_date_idx
                ON mail_messages (user_id, sent_date DESC)
            ');

            return;
        }

        Schema::table('mail_messages', function (Blueprint $table) {
            $table->index(['user_id', 'sent_date'], 'mail_messages_user_sent_date_idx');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS mail_messages_user_sent_date_idx');

            return;
        }

        Schema::table('mail_messages', function (Blueprint $table) {
            $table->dropIndex('mail_messages_user_sent_date_idx');
        });
    }
};
