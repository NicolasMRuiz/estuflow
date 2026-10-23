@if(! $recent || ($requireProvider ?? false) && ! $providerRecent)
    <div class="profile-reauth">
        <h3>Confirmá tu identidad</h3>
        <p>Esta confirmación dura 10 minutos y protege los cambios de tu cuenta.</p>
        @if($user->password !== null && ! ($requireProvider ?? false))
            <form method="POST" action="{{ route('reauth.password') }}" novalidate data-auth-form>@csrf
                <x-field name="password" label="Contraseña actual" type="password" autocomplete="current-password" maxlength="128" />
                <x-action type="submit" variant="secondary">Confirmar identidad</x-action>
            </form>
        @endif
        @foreach($linked as $provider)
            @if($providers->enabled($provider))<form method="POST" action="{{ route('oauth.reauthenticate', $provider) }}">@csrf<x-action type="submit" variant="secondary">Confirmar identidad con {{ \App\Auth\OAuthProviders::LABELS[$provider] }}</x-action></form>@endif
        @endforeach
        @if($user->password === null && ! collect($linked)->contains(fn ($provider) => $providers->enabled($provider)))<p>Tu proveedor vinculado no está disponible. Volvé a intentar cuando esté habilitado.</p>@endif
    </div>
@else
    <p class="academic-hint">Tu identidad está confirmada para continuar.</p>
@endif
