<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

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
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for public endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('property-inquiry', function (Request $request): Limit {
            return Limit::perMinute((int) config('properties.inquiry_rate_limit'))
                ->by((string) $request->ip())
                ->response(function (Request $request): RedirectResponse {
                    Inertia::flash('toast', [
                        'type' => 'warning',
                        'message' => 'Has enviado varias consultas seguidas. Espera un momento antes de intentarlo de nuevo.',
                    ]);

                    return to_route('properties.show', $request->route('property'));
                });
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
