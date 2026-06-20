<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\EncryptedEmailUserProvider;
use App\Models\Document;
use App\Policies\DocumentPolicy;
use App\Models\BusinessEntity;
use App\Policies\BusinessEntityPolicy;
use App\Models\Asset;
use App\Policies\AssetPolicy;
use App\Models\ContactList;
use App\Policies\ContactListPolicy;
use App\Models\EmailTemplate;
use App\Policies\EmailTemplatePolicy;
use App\Models\Commitment;
use App\Models\ComplianceDocumentFile;
use App\Models\ComplianceYearRecord;
use App\Models\Reminder;
use App\Policies\CommitmentPolicy;
use App\Policies\ComplianceDocumentFilePolicy;
use App\Policies\ComplianceYearRecordPolicy;
use App\Policies\ReminderPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Document::class => DocumentPolicy::class,
        BusinessEntity::class => BusinessEntityPolicy::class,
        Asset::class => AssetPolicy::class,
        ContactList::class => ContactListPolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
        Reminder::class => ReminderPolicy::class,
        Commitment::class => CommitmentPolicy::class,
        ComplianceDocumentFile::class => ComplianceDocumentFilePolicy::class,
        ComplianceYearRecord::class => ComplianceYearRecordPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Auth::provider('encrypted-eloquent', function ($app, array $config) {
            return new EncryptedEmailUserProvider(
                $app['hash'],
                $config['model']
            );
        });
    }
}