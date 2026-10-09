<x-auth-layout title="Ingresar">
    <span class="eyebrow">QUÉ BUENO TENERTE DE VUELTA</span>
    <h2>Retomá tu camino.</h2>
    <p class="auth-intro">Ingresá a tu espacio personal de EstuFlow.</p>
    @if(session('status'))<div class="success-message" role="status">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('login') }}" novalidate data-auth-form>
        @csrf
        <x-form-errors />
        <x-field name="email" label="Email" type="email" autocomplete="email" maxlength="254" placeholder="vos@ejemplo.com" />
        <x-field name="password" label="Contraseña" type="password" autocomplete="current-password" maxlength="128" />
        <p class="form-link"><a href="{{ route('password.request') }}">¿Olvidaste la contraseña?</a></p>
        <button class="button primary" type="submit" data-busy-label="Ingresando…"><span>Ingresar</span><x-icon name="arrow" size="20" /></button>
    </form>
    <x-social-login />
    <p class="auth-switch">¿Es tu primera vez? <a href="{{ route('register') }}">Creá tu cuenta</a></p>
</x-auth-layout>
