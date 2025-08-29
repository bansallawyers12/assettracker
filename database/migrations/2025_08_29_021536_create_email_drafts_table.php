<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('from_email');
            $table->string('to_email');
            $table->string('cc_email')->nullable();
            $table->string('bcc_email')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->json('attachments')->nullable(); // Store attachment metadata
            $table->unsignedBigInteger('business_entity_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // For future scheduling
            $table->timestamps();
            
            // Add foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_entity_id')->references('id')->on('business_entities')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('email_templates')->onDelete('set null');
            
            // Add indexes for better performance
            $table->index('user_id');
            $table->index('business_entity_id');
            $table->index('scheduled_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_drafts');
    }
};
