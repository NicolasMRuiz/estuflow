<x-academic-layout title="Cambiar email">
    <x-academic-header title="Mi perfil" :inline-errors="['email', 'password']" />
    <section class="academic-intro"><span class="eyebrow">SEGURIDAD DE TU CUENTA</span><h1>Cambiar email</h1><p>Tu email activo seguirá siendo el mismo hasta que confirmes ambas direcciones.</p></section>
    <section class="academic-panel profile-form"><h2>Email actual</h2><p class="wrap-anywhere">{{ $user->email }}</p><p class="academic-hint">Verificado · Usalo para ingresar mientras el cambio esté pendiente.</p>
        @include('profile.reauthenticate')
        @if($pending)
            <div class="academic-notice"><h3>Cambio {{ $pending->expires_at->isPast() ? 'vencido' : 'pendiente' }}</h3><p>Nuevo email: <span class="wrap-anywhere">{{ $pending->proposed_email }}</span></p>
                @foreach(['current' => 'Email actual', 'proposed' => 'Email nuevo'] as $purpose => $label)
                    <p>{{ $label }}: {{ $pending->getAttribute($purpose.'_confirmed_at') ? 'confirmado' : 'falta confirmar' }}.
                    @if(! $pending->getAttribute($purpose.'_confirmed_at')){{ match($pending->getAttribute($purpose.'_delivery')) { 'sent' => 'Correo aceptado por el servidor de envío; revisá tu bandeja y spam.', 'failed' => 'No pudimos confirmar el envío. Podés reenviar la solicitud.', default => 'Correo en espera de envío.' } }}@endif</p>
                @endforeach
                <p>Los enlaces vencen a los 60 minutos. Reenviar renueva ambos enlaces y sus confirmaciones.</p>
            </div>
            <div class="academic-actions"><form method="POST" action="{{ route('profile.email.resend') }}">@csrf<x-action type="submit" variant="secondary">Reenviar ambos correos</x-action></form><form method="POST" action="{{ route('profile.email.cancel') }}">@csrf<x-action type="submit" variant="link">Cancelar cambio de email</x-action></form></div>
        @endif
        <form method="POST" action="{{ route('profile.email.request') }}" novalidate data-auth-form>@csrf
            <x-field name="email" label="Nuevo email" type="email" autocomplete="email" maxlength="254" />
            <div class="academic-actions"><x-action type="submit">Solicitar cambio de email</x-action><x-action :href="route('profile.index')" variant="secondary">Volver a Mi perfil</x-action></div>
        </form>
    </section>
</x-academic-layout>
