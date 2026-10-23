<x-academic-layout title="Confirmar email">
    <x-academic-header title="Mi perfil" />
    <section class="academic-panel profile-form"><h1>Confirmar cambio de email</h1>
    @if($valid)<p>Para autorizar el cambio en tu cuenta actual, confirmá este correo. También necesitás confirmar la otra dirección.</p><form method="POST">@csrf<x-action type="submit">Confirmar este correo</x-action></form>
    @else<p>Este enlace no es válido, ya se utilizó o venció. Ingresá con la cuenta que solicitó el cambio y revisá tu perfil.</p>@endif
    <x-action :href="route('profile.email')" variant="link">Revisar cambio pendiente</x-action></section>
</x-academic-layout>
