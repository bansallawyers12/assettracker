<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_mail_message', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('mail_message_id');
            $table->timestamps();

            $table->unique(['asset_id', 'mail_message_id'], 'asset_mm_unique');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('mail_message_id')->references('id')->on('mail_messages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_mail_message');
    }
};


