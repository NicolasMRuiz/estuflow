<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio · EstuFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="dashboard-page" x-data="{ menuOpen: false }" @keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.menuButton.focus() }">
    <a href="#contenido" class="skip-link">Ir al contenido</a>
    <header class="mobile-bar">
        <x-brand />
        <button type="button" class="icon-button" x-ref="menuButton" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="navegacion" aria-label="Abrir o cerrar navegación"><x-icon name="menu" /></button>
    </header>
    <aside class="sidebar" :class="{ 'mobile-open': menuOpen }" id="navegacion">
        <div class="sidebar-brand"><x-brand /><span class="sidebar-subtitle">Tu espacio académico</span></div>
        <nav aria-label="Navegación principal">
            <span class="nav-label">MI ESPACIO</span>
            <a href="{{ route('dashboard') }}" aria-current="page" class="nav-item active" @click="menuOpen = false"><x-icon name="home" size="21" /><span>Inicio</span><span class="active-dot" aria-hidden="true"></span></a>
            @foreach(['Mi carrera' => 'book', 'Calendario' => 'calendar', 'Materias' => 'layers', 'Planificador' => 'route'] as $label => $icon)
                <div class="nav-item pending" aria-disabled="true"><x-icon :name="$icon" size="21" /><span>{{ $label }}</span><span class="soon">Próximo</span></div>
            @endforeach
        </nav>
        <div class="sidebar-bottom"><div class="sidebar-note"><x-icon name="spark" size="23" /><p>Cada camino es distinto.<br><strong>Este es el tuyo.</strong></p></div><span class="sidebar-version">EstuFlow · Primera entrega</span></div>
    </aside>
    <main class="dashboard-main" id="contenido">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
