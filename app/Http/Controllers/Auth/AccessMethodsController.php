<?php

namespace App\Http\Controllers\Auth;

use App\Account\Reauthentication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmAccessPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccessMethodsController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return to_route('profile.index');
    }

    public function confirmPassword(ConfirmAccessPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasVerifiedEmail(), 403);
        $data = $request->validated();
        if ($user->password === null || ! Hash::check($data['password'], $user->password)) {
            return to_route(Reauthentication::destination($request))->withErrors(['auth' => 'No pudimos confirmar tu identidad con esa contraseña.']);
        }
        self::confirm($request, $user);

        return to_route(Reauthentication::destination($request))->with('status', 'Identidad confirmada durante 10 minutos. Podés continuar.');
    }

    public static function confirm(Request $request, User $user): void
    {
        Reauthentication::confirm($request, $user);
    }

    public static function recentlyAuthenticated(Request $request): bool
    {
        return Reauthentication::recent($request);
    }
}
