<x-auth-layout title="Crear cuenta">
    <span class="eyebrow">EMPECEMOS POR VOS</span>
    <h2>Tu camino empieza acá.</h2>
    <p class="auth-intro">Creá tu cuenta y hacé espacio para lo que viene.</p>
    @if($errors->any())<div class="error-summary" role="alert">Revisá los campos indicados para crear tu cuenta.</div>@endif
    <form method="POST" action="{{ route('register') }}" x-data="{ busy: false }" @submit="busy = true" :aria-busy="busy">
        @csrf
        <x-field name="name" label="Tu nombre" autocomplete="name" maxlength="100" placeholder="¿Cómo te llamás?" />
        <x-field name="email" label="Email" type="email" autocomplete="email" maxlength="254" placeholder="vos@ejemplo.com" />
        <x-field name="password" label="Contraseña" type="password" autocomplete="new-password" minlength="10" hint="Usá al menos 10 caracteres." />
        <x-field name="password_confirmation" label="Confirmar contraseña" type="password" autocomplete="new-password" minlength="10" />
        <button class="button primary" type="submit" :disabled="busy"><span x-text="busy ? 'Creando tu cuenta…' : 'Crear mi cuenta'">Crear mi cuenta</span><x-icon name="arrow" size="20" /></button>
    </form>
    <p class="auth-switch">¿Ya tenés una cuenta? <a href="{{ route('login') }}">Ingresá</a></p>
</x-auth-layout>
