<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\InternalRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $request->session()->put('auth.password_confirmed_at', time());
        $request->session()->put('oauth.reauthenticated_user_id', $request->user()->id);

        return InternalRedirect::afterLogin($request);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($request->boolean('recover') ? 'password.request' : 'login')->with('status', 'Cerraste sesión correctamente.');
    }
}
