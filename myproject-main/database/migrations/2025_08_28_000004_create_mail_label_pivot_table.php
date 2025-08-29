<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_label_mail_message', function (Blueprint $table) {
            $table->unsignedBigInteger('mail_message_id');
            $table->unsignedBigInteger('mail_label_id');
            $table->primary(['mail_message_id', 'mail_label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_label_mail_message');
    }
};


