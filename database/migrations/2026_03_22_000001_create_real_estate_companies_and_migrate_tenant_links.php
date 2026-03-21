<?php

use App\Models\BusinessEntity;
use App\Models\RealEstateCompany;
use App\Models\RealEstateCompanyContact;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real estate agencies are separate from business_entities (your companies).
     */
    public function up(): void
    {
        Schema::create('real_estate_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('real_estate_company_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('real_estate_company_id')->constrained('real_estate_companies')->cascadeOnDelete();
            $table->string('contact_person_name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'real_estate_company_id')) {
                $table->foreignId('real_estate_company_id')
                    ->nullable()
                    ->after('is_real_estate_managed')
                    ->constrained('real_estate_companies')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('tenants', 'real_estate_business_entity_id')) {
            /** @var array<int, int> $beIdToRealEstateCompanyId */
            $beIdToRealEstateCompanyId = [];

            Tenant::query()
                ->whereNotNull('real_estate_business_entity_id')
                ->orderBy('id')
                ->each(function (Tenant $tenant) use (&$beIdToRealEstateCompanyId) {
                    $beId = (int) $tenant->real_estate_business_entity_id;
                    if (isset($beIdToRealEstateCompanyId[$beId])) {
                        $tenant->real_estate_company_id = $beIdToRealEstateCompanyId[$beId];
                        $tenant->save();

                        return;
                    }

                    $be = BusinessEntity::query()->find($beId);
                    if (!$be) {
                        return;
                    }

                    $company = RealEstateCompany::create([
                        'user_id' => $be->user_id,
                        'name' => $be->legal_name,
                        'email' => $be->registered_email,
                        'phone' => $be->phone_number,
                        'address' => $be->registered_address,
                    ]);

                    $be->load(['persons.person']);
                    foreach ($be->persons as $ep) {
                        if (!$ep->person) {
                            continue;
                        }
                        $p = $ep->person;
                        $fullName = trim(($p->first_name ?? '').' '.($p->last_name ?? ''));
                        if ($fullName === '') {
                            continue;
                        }
                        RealEstateCompanyContact::create([
                            'real_estate_company_id' => $company->id,
                            'contact_person_name' => $fullName,
                            'email' => $p->email,
                            'phone' => $p->phone_number,
                        ]);
                    }

                    $beIdToRealEstateCompanyId[$beId] = $company->id;
                    $tenant->real_estate_company_id = $company->id;
                    $tenant->save();
                });

            Schema::table('tenants', function (Blueprint $table) {
                $table->dropForeign(['real_estate_business_entity_id']);
                $table->dropColumn('real_estate_business_entity_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'real_estate_company_id')) {
                $table->dropForeign(['real_estate_company_id']);
                $table->dropColumn('real_estate_company_id');
            }
        });

        Schema::dropIfExists('real_estate_company_contacts');
        Schema::dropIfExists('real_estate_companies');

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'real_estate_business_entity_id')) {
                $table->foreignId('real_estate_business_entity_id')
                    ->nullable()
                    ->after('is_real_estate_managed')
                    ->constrained('business_entities')
                    ->nullOnDelete();
            }
        });
    }
};
