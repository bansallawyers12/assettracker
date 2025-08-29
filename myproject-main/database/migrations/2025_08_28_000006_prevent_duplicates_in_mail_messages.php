<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('mail_messages', 'source_hash')) {
                $table->string('source_hash')->nullable()->after('source_path');
            }

            $table->unique(['user_id', 'gmail_id'], 'uniq_user_gmail_id');
            $table->unique(['user_id', 'message_id'], 'uniq_user_message_id');
            $table->unique(['user_id', 'source_hash'], 'uniq_user_source_hash');
        });
    }

    public function down(): void
    {
        Schema::table('mail_messages', function (Blueprint $table) {
            $table->dropUnique('uniq_user_gmail_id');
            $table->dropUnique('uniq_user_message_id');
            $table->dropUnique('uniq_user_source_hash');
            if (Schema::hasColumn('mail_messages', 'source_hash')) {
                $table->dropColumn('source_hash');
            }
        });
    }
};


