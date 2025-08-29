<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mail_message_id')->index();
            $table->string('filename');
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('storage_path');
            $table->boolean('is_inline')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_attachments');
    }
};


