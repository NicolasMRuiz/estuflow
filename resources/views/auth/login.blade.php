<x-auth-layout title="Ingresar">
    <span class="eyebrow">QUÉ BUENO TENERTE DE VUELTA</span>
    <h2>Retomá tu camino.</h2>
    <p class="auth-intro">Ingresá a tu espacio personal de EstuFlow.</p>
    @if(session('status'))<div class="success-message" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error-summary" role="alert">No pudimos iniciar sesión. Revisá los datos indicados.</div>@endif
    <form method="POST" action="{{ route('login') }}" x-data="{ busy: false }" @submit="busy = true" :aria-busy="busy">
        @csrf
        <x-field name="email" label="Email" type="email" autocomplete="email" maxlength="254" placeholder="vos@ejemplo.com" />
        <x-field name="password" label="Contraseña" type="password" autocomplete="current-password" />
        <button class="button primary" type="submit" :disabled="busy"><span x-text="busy ? 'Ingresando…' : 'Ingresar'">Ingresar</span><x-icon name="arrow" size="20" /></button>
    </form>
    <p class="auth-switch">¿Es tu primera vez? <a href="{{ route('register') }}">Creá tu cuenta</a></p>
</x-auth-layout>
