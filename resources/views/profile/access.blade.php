<h3>Métodos de acceso</h3>
<p>{{ $user->password === null ? 'Ingresás con un proveedor vinculado.' : 'Tenés una contraseña configurada.' }}</p>
@include('profile.reauthenticate')
<div class="profile-methods">
@foreach(\App\Auth\OAuthProviders::LABELS as $provider => $label)
    <div class="profile-method"><strong>{{ $label }}</strong>
    @if(in_array($provider, $linked, true))<span>Vinculado a tu cuenta.</span>
    @elseif($providers->enabled($provider))<span>{{ $recent ? 'Disponible para vincular.' : 'Confirmá tu identidad para vincularlo.' }}</span>
        @if($recent)<form method="POST" action="{{ route('oauth.link', $provider) }}">@csrf<x-action type="submit" variant="secondary">Vincular {{ $label }}</x-action></form>@endif
    @else<span>No disponible por el momento.</span>@endif
    </div>
@endforeach
</div>
