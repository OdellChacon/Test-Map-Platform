<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(
                config('outage.auth_rate_limit.decay_minutes'),
                config('outage.auth_rate_limit.attempts'),
            )->by(Str::transliterate(Str::lower($request->input('email'))).'|'.$request->ip());
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

        RateLimiter::for('confirmations', function (Request $request) {
            return Limit::perMinutes(
                config('outage.confirmation_rate_limit.decay_minutes'),
                config('outage.confirmation_rate_limit.attempts'),
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('report-mutations', function (Request $request) {
            return Limit::perMinutes(
                config('outage.report_mutation_rate_limit.decay_minutes'),
                config('outage.report_mutation_rate_limit.attempts'),
            )->by($request->user()?->id ?: $request->ip());
        });
    }
}
