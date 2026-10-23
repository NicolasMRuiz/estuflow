@props(['title', 'inlineErrors' => []])
<header class="dashboard-header">
    <div class="breadcrumb"><span>Mi espacio</span><span aria-hidden="true">/</span><strong>{{ $title }}</strong></div>
    @if(session('status'))<p class="header-status" role="status">{{ session('status') }}</p>@endif
    <div class="header-actions">@if(auth()->user()?->hasVerifiedEmail())<livewire:notification-bell />@endif<a class="access-link" href="{{ route('profile.index') }}">Mi perfil</a>
        <form method="POST" action="{{ route('logout') }}" x-data="{ busy: false }" @submit="busy = true">@csrf<button type="submit" class="logout-button" :disabled="busy"><x-icon name="logout" size="19" /><span x-text="busy ? 'Saliendo…' : 'Cerrar sesión'">Cerrar sesión</span></button></form>
    </div>
</header>
<x-page-errors :inline="$inlineErrors" />
