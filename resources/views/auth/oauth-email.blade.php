<x-auth-layout title="Completá tu email">
    <span class="eyebrow">UN PASO MÁS</span>
    <h2>¿A qué email te escribimos?</h2>
    <p class="auth-intro">El proveedor no compartió una dirección utilizable. Indicá tu email para verificarlo en EstuFlow. Este paso vence en 10 minutos.</p>
    <form method="POST" action="{{ route('oauth.email.store') }}" novalidate data-auth-form>
        @csrf
        <x-form-errors />
        <x-field name="email" label="Email" type="email" autocomplete="email" maxlength="254" placeholder="vos@ejemplo.com" />
        <button class="button primary" type="submit">Continuar y verificar email <x-icon name="arrow" size="20" /></button>
    </form>
    <p class="auth-switch"><a href="{{ route('login') }}">Volver al ingreso</a></p>
</x-auth-layout>
