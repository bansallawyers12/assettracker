<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('gmail_id')->nullable()->index();
            $table->string('message_id')->nullable()->index();
            $table->string('subject')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable()->index();
            $table->text('recipients')->nullable();
            $table->timestamp('sent_date')->nullable()->index();
            $table->longText('html_content')->nullable();
            $table->longText('text_content')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_messages');
    }
};


