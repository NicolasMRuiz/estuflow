<?php

namespace App\Auth;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

class OAuthProviders
{
    public const LABELS = ['google' => 'Google', 'microsoft' => 'Microsoft'];

    public function enabled(string $provider): bool
    {
        if (! array_key_exists($provider, self::LABELS) || ! config("services.$provider.enabled")) {
            return false;
        }

        foreach (['client_id', 'client_secret', 'redirect'] as $key) {
            if (! is_string(config("services.$provider.$key")) || config("services.$provider.$key") === '') {
                return false;
            }
        }

        return config("services.$provider.redirect") === $this->callbackUrl($provider);
    }

    public function callbackUrl(string $provider): string
    {
        return rtrim((string) config('app.url'), '/')."/auth/$provider/callback";
    }

    public function driver(string $provider): AbstractProvider
    {
        abort_unless($this->enabled($provider), 404);
        $driver = Socialite::driver($provider);
        if (! $driver instanceof AbstractProvider) {
            throw new \LogicException('OAuth requiere un proveedor web con sesión.');
        }

        $driver->redirectUrl($this->callbackUrl($provider))->enablePKCE();
        $driver->setScopes($provider === 'google' ? ['openid', 'profile', 'email'] : ['openid', 'profile', 'email', 'User.Read']);
        $driver->setHttpClient(new Client(['timeout' => 10, 'connect_timeout' => 5]));

        return $driver;
    }

    public function profile(string $provider, AbstractProvider $driver, Request $request): OAuthProfile
    {
        $external = $driver->user(); // Socialite validates and consumes session state before exchanging the code.
        $issuer = 'https://accounts.google.com';
        $subject = $external->getId();

        if ($provider === 'microsoft') {
            if (! $driver instanceof MicrosoftProvider) {
                throw new InvalidStateException;
            }
            // getClaims() verifies signature and expiry through the maintained adapter.
            // Supplement its issuer check with exact audience and a request-bound nonce.
            $claims = $driver->getClaims();
            if ($claims === null) {
                throw new InvalidStateException;
            }
            $nonce = $request->session()->pull('oauth.nonce');
            $tenant = $claims->tid ?? null;
            if (! is_string($nonce) || ! is_string($claims->nonce ?? null) || ! hash_equals($nonce, $claims->nonce)
                || ($claims->aud ?? null) !== config('services.microsoft.client_id')
                || ! is_string($tenant) || ! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $tenant)
                || ($claims->iss ?? null) !== "https://login.microsoftonline.com/$tenant/v2.0") {
                throw new InvalidStateException;
            }
            $issuer = $claims->iss;
            $subject = $claims->sub ?? null;
        }

        if (! is_string($subject) || $subject === '' || strlen($subject) > 255) {
            throw new InvalidStateException;
        }

        $name = $external->getName();

        return new OAuthProfile(
            $provider, $issuer, $subject,
            is_string($name) && trim($name) !== '' ? mb_substr(trim($name), 0, 100) : 'Estudiante',
            OAuthProfile::usableEmail($external->getEmail()),
        );
    }
}
