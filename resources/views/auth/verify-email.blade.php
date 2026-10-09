<x-auth-layout title="Verificá tu email">
    <span class="eyebrow">UN PASO PARA CUIDAR TU CUENTA</span>
    <h2>Verificá tu email.</h2>
    <p class="auth-intro">Te enviamos un enlace a <strong class="account-email">{{ auth()->user()->email }}</strong>. Abrilo para confirmar tu dirección y entrar a tu espacio.</p>
    @if(session('status'))<div class="success-message" role="status">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('verification.send') }}" novalidate data-auth-form data-cooldown="{{ $retryAfter ?? 0 }}">
        @csrf
        <x-form-errors />
        <p class="auth-help">Si no encontrás el correo, revisá spam o correo no deseado. Podés solicitar un nuevo enlace cuando termine la espera.</p>
        <p class="cooldown-message" role="status" data-cooldown-message @if(!($retryAfter ?? 0)) hidden @endif>Podés volver a enviar en <span data-cooldown-seconds>{{ $retryAfter ?? 0 }}</span> segundos.</p>
        <button class="button primary" type="submit" data-busy-label="Enviando enlace…" @disabled(($retryAfter ?? 0) > 0)><span>Reenviar enlace</span><x-icon name="arrow" size="20" /></button>
        <noscript><p class="auth-help">El tiempo de espera se controla en el servidor. Cuando termine, <a href="{{ route('verification.notice') }}">actualizá esta página</a> para reenviar.</p></noscript>
    </form>
    <form class="auth-logout" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="text-button">Cerrar sesión</button>
    </form>
</x-auth-layout>
