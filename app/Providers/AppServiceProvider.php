<?php

namespace App\Providers;

use App\Support\TestingDatabase;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            TestingDatabase::assertSafe();
        }
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(10)->by($request->ip())->response(
            fn () => response('Demasiados registros. Esperá un minuto y volvé a intentar.', 429)
        ));
        RateLimiter::for('login-ip', fn (Request $request) => Limit::perMinute(30)->by($request->ip())->response(
            fn () => response('Demasiados intentos. Esperá un minuto y volvé a intentar.', 429)
        ));
    }
}
