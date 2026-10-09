<?php

namespace App\Providers;

use App\Models\User;
use App\Support\InternalRedirect;
use App\Support\TestingDatabase;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            TestingDatabase::assertSafe();
        }
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('microsoft', Provider::class);
        });
        // Security links and redirects never inherit an untrusted HTTP Host.
        URL::forceRootUrl((string) config('app.url'));
        URL::forceScheme(parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http');
        $limited = fn (Request $request, array $headers) => InternalRedirect::back($request, $request->user()?->hasVerifiedEmail() ? 'access-methods.index' : ($request->user() ? 'verification.notice' : 'login'))->withErrors([
            'auth' => 'Demasiados intentos. Volvé a probar en '.($headers['Retry-After'] ?? 60).' segundos.',
        ])->withInput($request->only(['name', 'email']))->withHeaders($headers);
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(10)->by($request->ip())->response($limited));
        RateLimiter::for('login-ip', fn (Request $request) => Limit::perMinute(30)->by($request->ip())->response($limited));
        RateLimiter::for('password-email', function (Request $request) use ($limited): array {
            $email = is_string($request->input('email')) ? User::normalizeEmail($request->input('email')) : '';

            return [
                Limit::perMinute(10)->by('reset-ip:'.$request->ip())->response($limited),
                Limit::perMinute(1)->by('reset-email:'.hash('sha256', $email))->response($limited),
            ];
        });
        RateLimiter::for('password-update', fn (Request $request) => Limit::perMinute(10)->by('reset-update:'.$request->ip())->response($limited));
        RateLimiter::for('verification-ip', fn (Request $request) => Limit::perMinute(20)->by('verification:'.$request->ip())->response($limited));
        RateLimiter::for('oauth', fn (Request $request) => Limit::perMinute(30)->by('oauth:'.$request->ip())->response($limited));
        RateLimiter::for('reauth', fn (Request $request) => Limit::perMinute(5)->by('reauth:'.$request->user()?->getAuthIdentifier().'|'.$request->ip())->response($limited));
    }
}
