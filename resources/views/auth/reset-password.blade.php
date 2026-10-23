<x-auth-layout title="Nueva contraseña">
    <span class="eyebrow">UN NUEVO COMIENZO</span>
    <h2>Elegí una contraseña.</h2>
    <p class="auth-intro">Elegí una contraseña de al menos 10 caracteres para volver a ingresar.</p>
    <form method="POST" action="{{ route('password.update') }}" novalidate data-auth-form>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form-errors />
        <x-field name="email" label="Email" type="email" autocomplete="email" maxlength="254" :value="is_string(request()->query('email')) ? request()->query('email') : ''" />
        <x-field name="password" label="Contraseña" type="password" autocomplete="new-password" minlength="10" maxlength="128" hint="Usá al menos 10 caracteres." />
        <x-field name="password_confirmation" label="Confirmar contraseña" type="password" autocomplete="new-password" minlength="10" maxlength="128" />
        <button class="button primary" type="submit" data-busy-label="Guardando contraseña…"><span>Guardar nueva contraseña</span><x-icon name="arrow" size="20" /></button>
    </form>
    <p class="auth-switch">¿El enlace ya no funciona? <a href="{{ route('password.request') }}">Solicitá otro enlace</a></p>
</x-auth-layout>
