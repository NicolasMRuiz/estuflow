<x-auth-layout title="Métodos de acceso">
    <span class="eyebrow">TU CUENTA</span>
    <h2>Métodos de acceso</h2>
    <p class="auth-intro">Administrá cómo ingresás a tu cuenta de EstuFlow. Para agregar un proveedor, primero confirmá tu identidad.</p>
    @if(session('status'))<div class="success-message" role="status">{{ session('status') }}</div>@endif
    <x-form-errors />
    <div class="access-methods">
        <section class="access-method" aria-labelledby="password-method">
            <h3 id="password-method">Contraseña</h3>
            @if($user->password !== null)
                <p class="method-status">Tenés una contraseña configurada.</p>
                @unless($recent)
                    <form method="POST" action="{{ route('reauth.password') }}" novalidate data-auth-form>
                        @csrf
                        <x-field name="password" label="Contraseña actual" type="password" autocomplete="current-password" maxlength="128" />
                        <button class="button secondary" type="submit">Confirmar identidad</button>
                    </form>
                @endunless
            @else
                <p class="method-status">Ingresás con un proveedor. Podés establecer una contraseña mediante un enlace enviado a tu email; tus vínculos se conservan.</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <input type="hidden" name="recover" value="1">
                    <button class="button secondary" type="submit">Cerrar sesión y recuperar acceso</button>
                </form>
            @endif
        </section>
        @foreach(\App\Auth\OAuthProviders::LABELS as $provider => $label)
            <section class="access-method" aria-labelledby="method-{{ $provider }}">
                <h3 id="method-{{ $provider }}">{{ $label }}</h3>
                @if(in_array($provider, $linked, true))
                    <p class="method-status">Vinculado a tu cuenta.</p>
                    @if($providers->enabled($provider) && ! $recent)
                        <form method="POST" action="{{ route('oauth.reauthenticate', ['provider' => $provider]) }}">
                            @csrf
                            <button class="button secondary" type="submit">Confirmar identidad con {{ $label }}</button>
                        </form>
                    @endif
                @elseif($providers->enabled($provider))
                    <p class="method-status">{{ $recent ? 'Listo para vincular.' : 'Confirmá tu identidad para vincularlo.' }}</p>
                    @if($recent)
                        <form method="POST" action="{{ route('oauth.link', ['provider' => $provider]) }}">
                            @csrf
                            <button class="button secondary" type="submit">Vincular {{ $label }}</button>
                        </form>
                    @endif
                @else
                    <p class="method-status">No disponible por el momento.</p>
                @endif
            </section>
        @endforeach
    </div>
    @if($recent)<p class="field-hint" role="status">Tu identidad está confirmada. Podés vincular proveedores durante 10 minutos desde esa confirmación.</p>@endif
    <p class="auth-switch"><a href="{{ route('dashboard') }}">Volver al inicio</a></p>
</x-auth-layout>
