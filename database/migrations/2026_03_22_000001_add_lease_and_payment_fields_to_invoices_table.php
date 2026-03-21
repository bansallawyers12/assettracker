<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('lease_id')->nullable()->after('business_entity_id')->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->after('lease_id')->constrained()->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('is_posted');
            $table->string('payment_method', 100)->nullable()->after('paid_at');
            $table->string('payment_reference', 255)->nullable()->after('payment_method');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('payment_reference');
            $table->unsignedInteger('reminder_count')->default(0)->after('last_reminder_sent_at');

            $table->index(['asset_id', 'issue_date']);
            $table->index(['lease_id', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['lease_id']);
            $table->dropForeign(['asset_id']);
            $table->dropIndex(['asset_id', 'issue_date']);
            $table->dropIndex(['lease_id', 'issue_date']);
            $table->dropColumn([
                'lease_id',
                'asset_id',
                'paid_at',
                'payment_method',
                'payment_reference',
                'last_reminder_sent_at',
                'reminder_count',
            ]);
        });
    }
};
