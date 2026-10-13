<?php

namespace App\Http\Controllers\Auth;

use App\Auth\OAuthProviders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmAccessPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccessMethodsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasVerifiedEmail(), 403);

        return view('auth.access-methods', [
            'user' => $user,
            'linked' => $user->oauthIdentities()->pluck('provider')->all(),
            'providers' => app(OAuthProviders::class),
            'recent' => self::recentlyAuthenticated($request),
        ]);
    }

    public function confirmPassword(ConfirmAccessPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasVerifiedEmail(), 403);
        $data = $request->validated();
        if ($user->password === null || ! Hash::check($data['password'], $user->password)) {
            return to_route('access-methods.index')->withErrors(['auth' => 'No pudimos confirmar tu identidad con esa contraseña.']);
        }
        self::confirm($request, $user);

        return to_route('access-methods.index')->with('status', 'Identidad confirmada durante 10 minutos. Ya podés vincular un proveedor.');
    }

    public static function confirm(Request $request, User $user): void
    {
        $request->session()->put('auth.password_confirmed_at', now()->timestamp);
        $request->session()->put('oauth.reauthenticated_user_id', $user->getAuthIdentifier());
    }

    public static function recentlyAuthenticated(Request $request): bool
    {
        $time = $request->session()->get('auth.password_confirmed_at');

        return is_int($time) && $time > now()->subMinutes(10)->timestamp && $time <= now()->timestamp
            && $request->session()->get('oauth.reauthenticated_user_id') === $request->user()?->getAuthIdentifier();
    }
}
