<x-auth-layout title="Recuperar acceso">
    <span class="eyebrow">VOLVÉ A TU CAMINO</span>
    <h2>Recuperá tu acceso.</h2>
    <p class="auth-intro">Ingresá tu email y te enviaremos un enlace para elegir una nueva contraseña.</p>
    @if(session('status'))<div class="success-message" role="status">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" novalidate data-auth-form>
        @csrf
        <x-form-errors />
        <x-field name="email" label="Email" type="email" autocomplete="email" maxlength="254" placeholder="vos@ejemplo.com" />
        <button class="button primary" type="submit" data-busy-label="Enviando solicitud…"><span>Enviar enlace</span><x-icon name="arrow" size="20" /></button>
    </form>
    <p class="auth-switch"><a href="{{ route('login') }}">Volver a ingresar</a></p>
</x-auth-layout>
