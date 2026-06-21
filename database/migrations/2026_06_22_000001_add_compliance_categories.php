<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, int> */
    private array $categorySortOrder = [
        'Tax & ATO' => 10,
        'ASIC & Company' => 20,
        'Property levies' => 10,
        'Insurance' => 20,
        'Depreciation' => 30,
        'Other' => 99,
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('compliance_document_types', 'category_group')) {
            Schema::table('compliance_document_types', function (Blueprint $table) {
                $table->string('category_group', 128)->nullable()->after('scope');
            });

            $this->seedCategoryGroups();
        }

        Schema::create('compliance_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_year_record_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->foreignId('compliance_category_id')
                ->nullable()
                ->after('compliance_year_record_id')
                ->constrained('compliance_categories')
                ->cascadeOnDelete();
            $table->string('checklist_label')->nullable()->after('compliance_document_type_id');
            $table->boolean('custom_label')->default(false)->after('checklist_label');
        });

        $this->backfillCategoriesAndFiles();

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->dropForeign(['compliance_document_type_id']);
            $table->dropUnique('compliance_year_type_unique');
        });

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->unsignedBigInteger('compliance_document_type_id')->nullable()->change();
            $table->foreign('compliance_document_type_id')
                ->references('id')
                ->on('compliance_document_types')
                ->nullOnDelete();
        });

        $this->addLabelUniqueIndex();
    }

    public function down(): void
    {
        $this->dropLabelUniqueIndex();

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->dropForeign(['compliance_document_type_id']);
        });

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->unsignedBigInteger('compliance_document_type_id')->nullable(false)->change();
            $table->foreign('compliance_document_type_id')
                ->references('id')
                ->on('compliance_document_types')
                ->restrictOnDelete();
            $table->unique(
                ['compliance_year_record_id', 'compliance_document_type_id'],
                'compliance_year_type_unique'
            );
        });

        Schema::table('compliance_document_files', function (Blueprint $table) {
            $table->dropForeign(['compliance_category_id']);
            $table->dropColumn(['compliance_category_id', 'checklist_label', 'custom_label']);
        });

        Schema::dropIfExists('compliance_categories');

        Schema::table('compliance_document_types', function (Blueprint $table) {
            $table->dropColumn('category_group');
        });
    }

    private function seedCategoryGroups(): void
    {
        $groups = [
            'itr' => 'Tax & ATO',
            'annual_accounts' => 'Tax & ATO',
            'bas_annual' => 'Tax & ATO',
            'bas_q1' => 'Tax & ATO',
            'bas_q2' => 'Tax & ATO',
            'bas_q3' => 'Tax & ATO',
            'bas_q4' => 'Tax & ATO',
            'asic_statement' => 'ASIC & Company',
            'other_entity' => 'Other',
            'land_tax' => 'Property levies',
            'council_rates' => 'Property levies',
            'water_rates' => 'Property levies',
            'insurance_certificate' => 'Insurance',
            'depreciation_schedule' => 'Depreciation',
            'other_asset' => 'Other',
        ];

        foreach ($groups as $code => $group) {
            DB::table('compliance_document_types')
                ->where('code', $code)
                ->update(['category_group' => $group]);
        }
    }

    private function backfillCategoriesAndFiles(): void
    {
        $types = DB::table('compliance_document_types')->get()->keyBy('id');
        $records = DB::table('compliance_year_records')->orderBy('id')->get();

        foreach ($records as $record) {
            $files = DB::table('compliance_document_files')
                ->where('compliance_year_record_id', $record->id)
                ->orderBy('id')
                ->get();

            $grouped = [];
            foreach ($files as $file) {
                $type = $types->get($file->compliance_document_type_id);
                $title = $type?->category_group ?? 'Other';
                $grouped[$title][] = $file;
            }

            foreach ($grouped as $title => $groupFiles) {
                $categoryId = DB::table('compliance_categories')->insertGetId([
                    'compliance_year_record_id' => $record->id,
                    'title' => $title,
                    'sort_order' => $this->categorySortOrder[$title] ?? 50,
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($groupFiles as $file) {
                    $type = $types->get($file->compliance_document_type_id);
                    DB::table('compliance_document_files')
                        ->where('id', $file->id)
                        ->update([
                            'compliance_category_id' => $categoryId,
                            'checklist_label' => $type?->label ?? 'Untitled',
                            'custom_label' => false,
                        ]);
                }
            }
        }
    }

    private function addLabelUniqueIndex(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS compliance_files_category_label_unique
                ON compliance_document_files (compliance_category_id, lower(trim(checklist_label)))
                WHERE compliance_category_id IS NOT NULL AND checklist_label IS NOT NULL
            ');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('compliance_document_files', function (Blueprint $table) {
                $table->unique(['compliance_category_id', 'checklist_label'], 'compliance_files_category_label_unique');
            });
        }
    }

    private function dropLabelUniqueIndex(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS compliance_files_category_label_unique');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('compliance_document_files', function (Blueprint $table) {
                $table->dropUnique('compliance_files_category_label_unique');
            });
        }
    }
};
