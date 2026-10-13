@props(['title'])
<header class="dashboard-header">
    <div class="breadcrumb"><span>Mi espacio</span><span aria-hidden="true">/</span><strong>{{ $title }}</strong></div>
    <div class="header-actions"><a class="access-link" href="{{ route('access-methods.index') }}">Métodos de acceso</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-button"><x-icon name="logout" size="19" />Cerrar sesión</button></form>
    </div>
</header>
@if(session('status'))<p class="academic-notice" role="status">{{ session('status') }}</p>@endif
<x-form-errors />
