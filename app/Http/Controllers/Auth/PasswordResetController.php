<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Jobs\RequestPasswordReset;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public const RESPONSE = 'Si ese email está asociado a una cuenta, recibirás las instrucciones para recuperar el acceso';

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(EmailRequest $request): RedirectResponse
    {
        try {
            RequestPasswordReset::dispatch($request->validated('email'));
        } catch (\Throwable) {
            return redirect()->route('password.request')->withErrors(['service' => 'No pudimos procesar la solicitud. Reintentá en unos minutos.'])->withInput($request->only('email'));
        }

        return redirect()->route('password.request')->with('status', self::RESPONSE);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        // Serialize resets for this email so concurrent requests cannot reuse a token.
        $status = DB::transaction(function () use ($request): string {
            User::where('email', $request->validated('email'))->lockForUpdate()->first();

            return Password::reset($request->safe()->only(['email', 'password', 'password_confirmation', 'token']), function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                event(new PasswordReset($user));
                $user->notify((new PasswordChanged)->afterCommit());
            });
        });

        if ($status !== Password::PasswordReset) {
            return redirect()->route('password.reset', ['token' => $request->validated('token')])->withErrors(['token' => 'Este enlace no es válido o venció. Solicitá uno nuevo.'])->withInput($request->safe()->only(['email']));
        }

        return redirect()->route('login')->with('status', 'Cambiaste tu contraseña. Ya podés ingresar con la nueva.');
    }
}
