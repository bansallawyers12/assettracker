<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('scope', ['entity', 'asset']);
            $table->string('category_group', 128)->nullable();
            $table->enum('frequency', ['annual', 'quarterly', 'ad_hoc'])->default('annual');
            $table->json('asset_types')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('compliance_year_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('fy_start_date');
            $table->date('fy_end_date');
            $table->text('notes')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('compliance_document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_year_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compliance_document_type_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['not_started', 'uploaded', 'lodged', 'paid'])->default('not_started');
            $table->date('due_date')->nullable();
            $table->date('lodged_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->string('file_name')->nullable();
            $table->string('path', 500)->nullable();
            $table->string('filetype', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['compliance_year_record_id', 'compliance_document_type_id'],
                'compliance_year_type_unique'
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                CREATE UNIQUE INDEX compliance_year_entity_unique
                ON compliance_year_records (business_entity_id, fy_start_date)
                WHERE asset_id IS NULL
            ');
            DB::statement('
                CREATE UNIQUE INDEX compliance_year_asset_unique
                ON compliance_year_records (business_entity_id, asset_id, fy_start_date)
                WHERE asset_id IS NOT NULL
            ');
        } else {
            Schema::table('compliance_year_records', function (Blueprint $table) {
                $table->unique(['business_entity_id', 'asset_id', 'fy_start_date'], 'compliance_year_scope_unique');
            });
        }

        (new \Database\Seeders\ComplianceDocumentTypeSeeder)->run();
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS compliance_year_entity_unique');
            DB::statement('DROP INDEX IF EXISTS compliance_year_asset_unique');
        }

        Schema::dropIfExists('compliance_document_files');
        Schema::dropIfExists('compliance_year_records');
        Schema::dropIfExists('compliance_document_types');
    }
};
