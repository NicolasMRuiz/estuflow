<x-auth-layout title="Crear cuenta">
    <span class="eyebrow">EMPECEMOS POR VOS</span>
    <h2>Tu camino empieza acá.</h2>
    <p class="auth-intro">Creá tu cuenta y hacé espacio para lo que viene.</p>
    <form method="POST" action="{{ route('register') }}" novalidate data-auth-form>
        @csrf
        <x-form-errors />
        <x-field name="name" label="Tu nombre" autocomplete="name" maxlength="100" placeholder="¿Cómo te llamás?" />
        <x-field name="email" label="Email" type="email" autocomplete="email" maxlength="254" placeholder="vos@ejemplo.com" />
        <x-field name="password" label="Contraseña" type="password" autocomplete="new-password" minlength="10" maxlength="128" hint="Usá al menos 10 caracteres." />
        <x-field name="password_confirmation" label="Confirmar contraseña" type="password" autocomplete="new-password" minlength="10" maxlength="128" />
        <button class="button primary" type="submit" data-busy-label="Creando tu cuenta…"><span>Crear mi cuenta</span><x-icon name="arrow" size="20" /></button>
    </form>
    <x-social-login />
    <p class="auth-switch">¿Ya tenés una cuenta? <a href="{{ route('login') }}">Ingresá</a></p>
</x-auth-layout>
