<?php

namespace App\Http\Controllers;

use App\Academic\AcademicProgress;
use App\Account\AccountSecurity;
use App\Account\EmailChanges;
use App\Account\Reauthentication;
use App\Auth\OAuthProviders;
use App\Http\Requests\ProfileRequest;
use App\Models\PendingEmailChange;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function index(Request $request, AcademicProgress $progress): View
    {
        $request->session()->put('profile.operation', 'access');

        return view('profile.index', $this->context($request) + ['enrollment' => $progress->active($request->user()), 'candidate' => $progress->candidate($request->user()), 'pending' => PendingEmailChange::where('user_id', $request->user()->id)->first()]);
    }

    public function name(ProfileRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['name' => $request->validated('name')])->save();

        return to_route('profile.index')->with('status', 'Guardamos tu nombre.');
    }

    public function email(Request $request): View
    {
        $request->session()->put('profile.operation', 'email');

        return view('profile.email', $this->context($request) + ['pending' => PendingEmailChange::where('user_id', $request->user()->id)->first()]);
    }

    public function requestEmail(ProfileRequest $request, EmailChanges $changes): RedirectResponse
    {
        $this->requireRecent($request);
        $changes->request($request->user(), $request->validated('email'));

        return to_route('profile.email')->with('status', 'Solicitud guardada. Confirmá ambos correos cuando lleguen; tu email activo se conserva.');
    }

    public function resend(Request $request, EmailChanges $changes): RedirectResponse
    {
        $this->requireRecent($request);
        $pending = PendingEmailChange::where('user_id', $request->user()->id)->first();
        if (! $pending) {
            return to_route('profile.email')->withErrors(['account' => 'No hay un cambio pendiente para reenviar.']);
        }
        $changes->request($request->user(), $pending->proposed_email);

        return to_route('profile.email')->with('status', 'Renovamos la solicitud. Los enlaces anteriores dejaron de funcionar; confirmá ambos correos nuevos.');
    }

    public function cancelEmail(Request $request, EmailChanges $changes): RedirectResponse
    {
        $changes->cancel($request->user());

        return to_route('profile.email')->with('status', 'Cancelaste el cambio. Tu email activo se conserva.');
    }

    public function confirmation(Request $request, string $change, string $purpose, string $token): View
    {
        $pending = PendingEmailChange::find($change);
        $valid = $pending?->accepts($request->user(), $purpose, $token) ?? false;

        return view('profile.confirm-email', ['valid' => $valid]);
    }

    public function confirm(Request $request, EmailChanges $changes, string $change, string $purpose, string $token): RedirectResponse
    {
        try {
            $complete = $changes->confirm($request, $change, $purpose, $token);
        } catch (ValidationException $exception) {
            return to_route('profile.email')->withErrors($exception->errors());
        }

        return to_route('profile.email')->with('status', $complete ? 'Tu email cambió y quedó verificado. Cerramos las otras sesiones de tu cuenta.' : 'Confirmamos este correo. Falta confirmar el otro; tu email activo se conserva.');
    }

    public function password(Request $request): View
    {
        $request->session()->put('profile.operation', 'password');

        return view('profile.password', $this->context($request));
    }

    public function updatePassword(ProfileRequest $request, AccountSecurity $security): RedirectResponse
    {
        DB::transaction(function () use ($request, $security): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($user->password !== null) {
                if (! is_string($request->validated('current_password')) || ! Hash::check($request->validated('current_password'), $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'La contraseña actual no es correcta.']);
                }
            } else {
                $this->requireRecent($request, provider: true);
            }
            $user->forceFill(['password' => $request->validated('password')])->save();
            $security->revokeOthers($user, $request, $user->email);
            $user->notify((new PasswordChanged)->afterCommit());
        });
        $request->user()->refresh();
        $security->renew($request);

        return to_route('profile.index')->with('status', 'Guardamos tu contraseña. Cerramos las otras sesiones de tu cuenta.');
    }

    private function requireRecent(Request $request, bool $provider = false): void
    {
        if (! Reauthentication::recent($request, $provider)) {
            throw ValidationException::withMessages(['account' => 'Confirmá tu identidad antes de continuar.']);
        }
    }

    /** @return array<string, mixed> */
    private function context(Request $request): array
    {
        return ['user' => $request->user(), 'linked' => $request->user()->oauthIdentities()->pluck('provider')->all(), 'providers' => app(OAuthProviders::class), 'recent' => Reauthentication::recent($request), 'providerRecent' => Reauthentication::recent($request, true)];
    }
}
