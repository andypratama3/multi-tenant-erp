<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Authorization\DomainAbilities;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        // Strict mode: raise exceptions for lazy loading, unguarded, missing attributes.
        // In production we log lazily but still raise on missing attributes/unguarded.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Use CarbonImmutable for all dates to prevent accidental mutation.
        Date::use(CarbonImmutable::class);

        // Keep localized date formatting consistent across web, API, and console.
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        // Prevent destructive schema commands from running on production.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Super admins bypass every policy check; all other users follow the
        // explicit ViewAny/View/Create/... permission matrix.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        foreach (DomainAbilities::policies() as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
