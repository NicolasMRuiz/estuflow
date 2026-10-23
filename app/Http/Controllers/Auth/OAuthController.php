<?php

namespace App\Http\Controllers\Auth;

use App\Account\Reauthentication;
use App\Auth\OAuthAccountException;
use App\Auth\OAuthAccounts;
use App\Auth\OAuthProfile;
use App\Auth\OAuthProviders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OAuthEmailRequest;
use App\Models\User;
use App\Support\InternalRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class OAuthController extends Controller
{
    public function __construct(private OAuthProviders $providers, private OAuthAccounts $accounts) {}

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        abort_if($request->user() !== null, 403);

        return $this->begin($request, $provider, 'login');
    }

    public function link(Request $request, string $provider): RedirectResponse
    {
        $this->verifiedUser($request);
        if (! AccessMethodsController::recentlyAuthenticated($request)) {
            return to_route(Reauthentication::destination($request))->withErrors(['oauth' => 'Confirmá tu identidad antes de vincular un proveedor.']);
        }

        return $this->begin($request, $provider, 'link');
    }

    public function reauthenticate(Request $request, string $provider): RedirectResponse
    {
        $user = $this->verifiedUser($request);
        abort_unless($user->oauthIdentities()->where('provider', $provider)->exists(), 403);

        return $this->begin($request, $provider, 'reauthenticate');
    }

    private function begin(Request $request, string $provider, string $intent): RedirectResponse
    {
        abort_unless($this->providers->enabled($provider), 404);
        $request->session()->forget(['oauth.pending', 'oauth.nonce', 'state', 'code_verifier']);
        $request->session()->put('oauth.attempt', [
            'provider' => $provider, 'intent' => $intent,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'return_to' => Reauthentication::destination($request),
        ]);
        $driver = $this->providers->driver($provider);
        $parameters = ['prompt' => $provider === 'microsoft' && $intent === 'reauthenticate' ? 'login' : 'select_account'];
        if ($provider === 'microsoft') {
            $parameters['nonce'] = Str::random(64);
            $request->session()->put('oauth.nonce', $parameters['nonce']);
        }

        return $driver->with($parameters)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless($this->providers->enabled($provider), 404);
        $attempt = $request->session()->pull('oauth.attempt');
        $destination = $request->user() ? (in_array($attempt['return_to'] ?? null, ['profile.index', 'profile.email', 'profile.password'], true) ? $attempt['return_to'] : 'access-methods.index') : 'login';
        try {
            $state = $request->query('state');
            $expected = $request->session()->get('state');
            if (! is_array($attempt) || ($attempt['provider'] ?? null) !== $provider
                || ($attempt['expires_at'] ?? 0) < now()->timestamp
                || ! is_string($state) || ! is_string($expected) || $expected === '' || ! hash_equals($expected, $state)) {
                throw new InvalidStateException;
            }
            if ($request->query('error') !== null) {
                return to_route($destination)->withErrors(['oauth' => 'El proveedor canceló o no autorizó el acceso. Podés reintentar; si tu institución lo restringe, consultá a su administrador.']);
            }

            $profile = $this->providers->profile($provider, $this->providers->driver($provider), $request);
            if (in_array($attempt['intent'] ?? null, ['link', 'reauthenticate'], true)) {
                $user = $this->verifiedUser($request);
                if ($user->getAuthIdentifier() !== ($attempt['user_id'] ?? null)) {
                    throw new InvalidStateException;
                }
                if ($attempt['intent'] === 'reauthenticate') {
                    if ($this->accounts->find($profile)?->user_id !== $user->id) {
                        throw new OAuthAccountException('Elegí un método que ya esté vinculado a tu cuenta para confirmar tu identidad.');
                    }
                    Reauthentication::confirm($request, $user, provider: true);

                    return to_route($destination)->with('status', 'Identidad confirmada durante 10 minutos. Podés continuar.');
                }
                if (! AccessMethodsController::recentlyAuthenticated($request)) {
                    throw new OAuthAccountException('La confirmación de identidad venció. Confirmala de nuevo antes de vincular.');
                }
                $this->accounts->link($user, $profile);

                return to_route($destination)->with('status', OAuthProviders::LABELS[$provider].' quedó vinculado a tu cuenta.');
            }
            if (($attempt['intent'] ?? null) !== 'login' || $request->user() !== null || ($attempt['user_id'] ?? null) !== null) {
                throw new InvalidStateException;
            }
            if (! $this->accounts->find($profile) && $profile->email === null) {
                $request->session()->put('oauth.pending', [...$profile->identity(), 'name' => $profile->name, 'expires_at' => now()->addMinutes(10)->timestamp]);

                return to_route('oauth.email');
            }

            return $this->authenticate($request, $this->accounts->login($profile));
        } catch (InvalidStateException) {
            return to_route($destination)->withErrors(['oauth' => 'No pudimos validar este intento de acceso. Iniciá nuevamente desde EstuFlow.']);
        } catch (OAuthAccountException $exception) {
            return to_route($destination)->withErrors(['oauth' => $exception->getMessage()]);
        } catch (Throwable) {
            // Provider exception payloads can contain tokens; never flash or log them.
            return to_route($destination)->withErrors(['oauth' => 'No pudimos conectar con el proveedor. Reintentá en unos minutos o usá otro método de acceso.']);
        } finally {
            $request->session()->forget(['state', 'code_verifier', 'oauth.nonce']);
        }
    }

    public function email(Request $request): View|RedirectResponse
    {
        if ($this->pendingProfile($request) === null) {
            return to_route('login')->withErrors(['oauth' => 'El paso para completar tu email venció. Volvé a ingresar con el proveedor.']);
        }

        return view('auth.oauth-email');
    }

    public function storeEmail(OAuthEmailRequest $request): RedirectResponse
    {
        $pending = $this->pendingProfile($request);
        if ($pending === null) {
            return to_route('login')->withErrors(['oauth' => 'El paso para completar tu email venció. Volvé a ingresar con el proveedor.']);
        }
        $profile = new OAuthProfile($pending->provider, $pending->issuer, $pending->subject, $pending->name, $request->validated('email'));
        try {
            $user = $this->accounts->login($profile);
        } catch (OAuthAccountException $exception) {
            return to_route('oauth.email')->withInput($request->only('email'))->withErrors(['oauth' => $exception->getMessage()]);
        }
        $request->session()->forget('oauth.pending');

        return $this->authenticate($request, $user);
    }

    private function pendingProfile(Request $request): ?OAuthProfile
    {
        $pending = $request->session()->get('oauth.pending');
        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->timestamp
            || ! isset($pending['provider'], $pending['issuer'], $pending['subject'], $pending['name'])
            || ! $this->providers->enabled($pending['provider'])) {
            $request->session()->forget('oauth.pending');

            return null;
        }

        return new OAuthProfile($pending['provider'], $pending['issuer'], $pending['subject'], $pending['name'], null);
    }

    private function authenticate(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();
        AccessMethodsController::confirm($request, $user);

        return InternalRedirect::afterLogin($request);
    }

    private function verifiedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasVerifiedEmail(), 403);

        return $user;
    }
}
