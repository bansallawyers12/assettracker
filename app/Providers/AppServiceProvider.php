<?php

namespace App\Providers;

use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use App\Support\HeaderSearchIndex;
use App\Support\PasswordPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Validator::replacer('uploaded', function (string $message, string $attribute, string $rule, array $parameters, \Illuminate\Validation\Validator $validator): string {
            $value = data_get($validator->getData(), $attribute);
            if (! $value instanceof UploadedFile) {
                return $message;
            }
            $detail = trim($value->getErrorMessage());
            if ($detail !== '') {
                return $detail;
            }
            if (! $value->isValid()) {
                return 'The file did not upload correctly (the server could not verify the temporary upload). Check PHP upload_tmp_dir, free disk space, upload_max_filesize, and post_max_size for your web SAPI (not only CLI), then retry.';
            }

            return $message;
        });

        Transaction::observe(TransactionObserver::class);

        Password::defaults(fn () => PasswordPolicy::rule());

        View::composer('layouts.navigation', function ($view) {
            if (! auth()->check() || ! Gate::allows('viewAny', BusinessEntity::class)) {
                $view->with('headerSearchIndex', []);

                return;
            }

            $view->with('headerSearchIndex', HeaderSearchIndex::build());
        });
    }
}
