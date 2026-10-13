<x-auth-layout title="Revisá el enlace">
    <span class="eyebrow">VOLVAMOS A INTENTAR</span>
    <h2>Este enlace no está disponible.</h2>
    <p class="auth-intro">El enlace de verificación venció, cambió o corresponde a otra cuenta. Tu cuenta sigue protegida.</p>
    <a class="button primary" href="{{ route('verification.notice') }}">Solicitar un nuevo enlace</a>
    @auth
        <form class="auth-logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-button">Cerrar sesión para usar otra cuenta</button>
        </form>
    @else
        <p class="auth-switch"><a href="{{ route('login') }}">Volver a ingresar</a></p>
    @endauth
</x-auth-layout>
