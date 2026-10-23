@props(['title'])
<header class="dashboard-header">
    <div class="breadcrumb"><span>Mi espacio</span><span aria-hidden="true">/</span><strong>{{ $title }}</strong></div>
    @if(session('status'))<p class="header-status" role="status">{{ session('status') }}</p>@endif
    <div class="header-actions"><a class="access-link" href="{{ route('access-methods.index') }}">Métodos de acceso</a>
        <form method="POST" action="{{ route('logout') }}" x-data="{ busy: false }" @submit="busy = true">@csrf<button type="submit" class="logout-button" :disabled="busy"><x-icon name="logout" size="19" /><span x-text="busy ? 'Saliendo…' : 'Cerrar sesión'">Cerrar sesión</span></button></form>
    </div>
</header>
@foreach($errors->getMessages() as $field => $messages)
    @unless($field === 'plan_id' || str_starts_with($field, 'entries.') || str_starts_with($field, 'entry.'))<p class="error-summary" role="alert" tabindex="-1" data-academic-error>{{ $messages[0] }}</p>@endunless
@endforeach
