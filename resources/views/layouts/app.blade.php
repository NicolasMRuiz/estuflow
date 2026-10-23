<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Inicio' }} · EstuFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="dashboard-page" x-data="{ menuOpen: false }" @keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.menuButton.focus() }">
    <noscript><style>.approval-field[x-cloak]{display:block!important}@media(max-width:800px){.sidebar{display:flex}.mobile-bar button{display:none}}</style></noscript>
    <a href="#contenido" class="skip-link">Ir al contenido</a>
    <header class="mobile-bar">
        <x-brand />
        <button type="button" class="icon-button" x-ref="menuButton" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="navegacion" aria-label="Abrir o cerrar navegación"><x-icon name="menu" /></button>
    </header>
    <aside class="sidebar" :class="{ 'mobile-open': menuOpen }" id="navegacion">
        <div class="sidebar-brand"><x-brand /><span class="sidebar-subtitle">Tu espacio académico</span></div>
        <nav aria-label="Navegación principal">
            <span class="nav-label">MI ESPACIO</span>
            @foreach(['dashboard' => ['Inicio', 'home'], 'career.index' => ['Mi carrera', 'book']] as $name => [$label, $icon])
                <a href="{{ route($name) }}" @if(request()->routeIs($name) || ($name === 'career.index' && request()->routeIs('career.*'))) aria-current="page" @endif class="nav-item {{ request()->routeIs($name) || ($name === 'career.index' && request()->routeIs('career.*')) ? 'active' : '' }}" @click="menuOpen = false"><x-icon :name="$icon" size="21" /><span>{{ $label }}</span></a>
            @endforeach
            @foreach(['Calendario' => 'calendar', 'Planificador' => 'route'] as $label => $icon)
                <div class="nav-item pending" aria-disabled="true"><x-icon :name="$icon" size="21" /><span>{{ $label }}</span><span class="soon">Próximo</span></div>
            @endforeach
        </nav>
        <div class="sidebar-bottom"><div class="sidebar-note"><x-icon name="spark" size="23" /><p>Cada camino es distinto.<br><strong>Este es el tuyo.</strong></p></div><span class="sidebar-version">EstuFlow · Tu espacio académico</span></div>
    </aside>
    <main class="dashboard-main" id="contenido" tabindex="-1">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
