@php($providers = app(\App\Auth\OAuthProviders::class))
@if($providers->enabled('google') || $providers->enabled('microsoft'))
    <div class="auth-divider"><span>O continuá con</span></div>
    <div class="social-login" aria-label="Otros métodos de acceso">
        @foreach(\App\Auth\OAuthProviders::LABELS as $provider => $label)
            @if($providers->enabled($provider))
                <a class="social-button" href="{{ route('oauth.redirect', ['provider' => $provider]) }}">
                    @if($provider === 'google')
                        <img src="{{ asset('images/google-g.png') }}" width="20" height="20" alt="" aria-hidden="true">
                    @else
                        <svg width="20" height="20" viewBox="0 0 21 21" aria-hidden="true"><path fill="#f25022" d="M1 1h9v9H1z"/><path fill="#7fba00" d="M11 1h9v9h-9z"/><path fill="#00a4ef" d="M1 11h9v9H1z"/><path fill="#ffb900" d="M11 11h9v9h-9z"/></svg>
                    @endif
                    <span>{{ $provider === 'google' ? 'Continuar con Google' : 'Iniciar sesión con Microsoft' }}</span>
                </a>
            @endif
        @endforeach
    </div>
@endif
