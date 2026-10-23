<x-academic-layout title="Contraseña">
    <x-academic-header title="Mi perfil" :inline-errors="['current_password', 'password', 'password_confirmation']" />
    <section class="academic-intro"><span class="eyebrow">SEGURIDAD DE TU CUENTA</span><h1>{{ $user->password === null ? 'Establecer contraseña' : 'Cambiar contraseña' }}</h1><p>Al guardar, cerraremos las otras sesiones de tu cuenta. Tus proveedores vinculados se conservan.</p></section>
    <section class="academic-panel profile-form">
        @if($user->password === null)<p>Ingresás con un proveedor. Confirmá tu identidad con ese proveedor antes de establecer tu primera contraseña.</p>@include('profile.reauthenticate', ['requireProvider' => true])@endif
        <form method="POST" action="{{ route('profile.password.update') }}" novalidate data-auth-form>@csrf
            @if($user->password !== null)<x-field name="current_password" label="Contraseña actual" type="password" autocomplete="current-password" maxlength="128" />@endif
            <x-field name="password" label="Nueva contraseña" type="password" autocomplete="new-password" minlength="10" maxlength="128" hint="Usá entre 10 y 128 caracteres." />
            <x-field name="password_confirmation" label="Confirmar nueva contraseña" type="password" autocomplete="new-password" maxlength="128" />
            <div class="academic-actions"><x-action type="submit">Guardar contraseña</x-action><x-action :href="route('profile.index')" variant="secondary">Cancelar</x-action></div>
        </form>
    </section>
</x-academic-layout>
