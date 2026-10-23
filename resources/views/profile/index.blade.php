<x-academic-layout title="Mi perfil">
    <x-academic-header title="Mi perfil" :inline-errors="['name', 'password']" />
    <section class="academic-intro"><span class="eyebrow">TU CUENTA</span><h1>Mi perfil</h1><p>Tu información y los ajustes de tu recorrido, en un solo lugar.</p></section>
    <div class="profile-grid">
        <section class="academic-panel profile-personal" aria-labelledby="personal-title">
            <div class="profile-heading"><span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><h2 id="personal-title">Datos personales</h2></div>
            <form method="POST" action="{{ route('profile.name') }}" novalidate data-auth-form>@csrf
                <x-field name="name" label="Tu nombre" autocomplete="name" :value="$user->name" maxlength="100" />
                <div class="academic-actions"><x-action type="submit">Guardar nombre</x-action><x-action :href="route('profile.index')" variant="secondary">Cancelar</x-action></div>
            </form>
            <div class="profile-email"><h3>Email actual</h3><p class="wrap-anywhere">{{ $user->email }}</p><p class="academic-hint">Email verificado</p><x-action :href="route('profile.email')" variant="link">Cambiar email</x-action>
            @if($pending)<p class="academic-notice">Tenés un cambio de email {{ $pending->expires_at->isPast() ? 'vencido' : 'pendiente' }}. <a href="{{ route('profile.email') }}">Revisar solicitud</a></p>@endif</div>
        </section>
        <section class="academic-panel profile-security" aria-labelledby="security-title"><h2 id="security-title">Seguridad y accesos</h2>
            <x-action :href="route('profile.password')" variant="secondary">{{ $user->password === null ? 'Establecer contraseña' : 'Cambiar contraseña' }}</x-action>
            @include('profile.access')
        </section>
        <section class="academic-panel profile-career" id="mi-carrera" aria-labelledby="career-title"><h2 id="career-title">Mi carrera</h2>
            @if($enrollment?->completed_at)<h3>{{ \App\Academic\Presentation::career($enrollment->plan) }}</h3><p>{{ \App\Academic\Presentation::plan($enrollment->plan) }}</p><div class="academic-actions"><x-action :href="route('career.index')" variant="secondary">Ver mis materias</x-action><x-action :href="route('profile.career')" variant="link">Cambiar carrera</x-action></div>
            @else<p>Todavía no confirmaste tu carrera.</p><x-action :href="route('profile.career')">Completar mi carrera</x-action>@endif
            @if($candidate)<p class="academic-notice">Borrador sin confirmar: {{ \App\Academic\Presentation::career($candidate->plan) }}. Tu carrera confirmada se conserva.</p><x-action :href="route('profile.career')" variant="link">Retomar configuración</x-action>@endif
        </section>
    </div>
</x-academic-layout>
