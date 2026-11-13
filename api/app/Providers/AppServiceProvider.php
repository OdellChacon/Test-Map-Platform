<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(
                config('outage.auth_rate_limit.decay_minutes'),
                config('outage.auth_rate_limit.attempts'),
            )->by($request->ip());
        });

        RateLimiter::for('reports', function (Request $request) {
            return Limit::perMinutes(
                config('outage.report_rate_limit.decay_minutes'),
                config('outage.report_rate_limit.attempts'),
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinutes(
                config('outage.public_rate_limit.decay_minutes'),
                config('outage.public_rate_limit.attempts'),
            )->by($request->ip());
        });
    }
}
