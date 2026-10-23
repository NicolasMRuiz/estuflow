<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationController extends Controller
{
    private function key(Request $request): string
    {
        return 'verify:'.$request->user()->getAuthIdentifier();
    }

    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email', ['retryAfter' => RateLimiter::availableIn($this->key($request))]);
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        $key = $this->key($request);
        if (RateLimiter::increment($key, 60) > 1) {
            return redirect()->route('verification.notice')->withErrors(['auth' => 'Podés reenviar el correo en '.RateLimiter::availableIn($key).' segundos.']);
        }
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable) {
            RateLimiter::clear($key);

            return redirect()->route('verification.notice')->withErrors(['service' => 'No pudimos solicitar el correo. Reintentá en unos minutos.']);
        }

        return redirect()->route('verification.notice')->with('status', 'Enviamos un nuevo enlace de verificación. Revisá tu correo.');
    }

    public function verify(Request $request, string $id, string $hash): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $request->hasValidSignature() || ! hash_equals((string) $user->getAuthIdentifier(), $id) || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->view('auth.verification-invalid', [], 403);
        }
        $transition = DB::transaction(function () use ($user, $hash): User|bool {
            $locked = User::query()->lockForUpdate()->findOrFail($user->getAuthIdentifier());
            if (! hash_equals(sha1($locked->getEmailForVerification()), $hash)) {
                return false;
            }

            return ! $locked->hasVerifiedEmail() && $locked->markEmailAsVerified() ? $locked : true;
        });
        if ($transition === false) {
            return response()->view('auth.verification-invalid', [], 403);
        }
        if ($transition instanceof User) {
            event(new Verified($transition));
        }

        return redirect()->route('dashboard')->with('status', 'Tu email quedó verificado.');
    }
}
