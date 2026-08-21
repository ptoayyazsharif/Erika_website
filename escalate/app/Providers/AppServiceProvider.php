<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Administrator overrides, laid over the config files.
         *
         * Here rather than at each call site so that nothing downstream — Quota,
         * Ceiling, Plan, the two AI clients, Cashier — has to know settings can
         * come from a database. They all keep reading config(), which means
         * there is no second path by which a value could be read unoverridden.
         *
         * Cached, so this is one array lookup per request rather than a query.
         * Settings::apply() no-ops safely when the table does not exist yet,
         * which is the state every `migrate` on a fresh database starts in.
         */
        Settings::apply();
    }
}
