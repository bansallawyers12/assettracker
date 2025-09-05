<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_entity_mail_message', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_entity_id');
            $table->unsignedBigInteger('mail_message_id');
            $table->timestamps();

            $table->unique(['business_entity_id', 'mail_message_id'], 'be_mm_unique');
            $table->foreign('business_entity_id')->references('id')->on('business_entities')->onDelete('cascade');
            $table->foreign('mail_message_id')->references('id')->on('mail_messages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_entity_mail_message');
    }
};


