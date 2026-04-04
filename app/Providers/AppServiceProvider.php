<?php

namespace App\Providers;

use App\Models\BusinessEntity;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use App\Support\HeaderSearchIndex;
use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\Gate;
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
