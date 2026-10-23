<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · EstuFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="auth-page">
    <a href="#contenido" class="skip-link">Ir al contenido</a>
    <aside class="auth-story" aria-label="EstuFlow">
        <x-brand />
        <div class="story-main">
            <span class="eyebrow light">TU CAMINO, A TU RITMO</span>
            <h1>Más claridad.<br>Más calma.<br><span>Tu próximo paso.</span></h1>
            <p>Un lugar para darle forma a tu vida académica, empezando por vos.</p>
            <div class="flow-art" aria-hidden="true"><div class="art-line"></div><span class="art-node n1"><x-icon name="book" /></span><span class="art-node n2"><x-icon name="calendar" /></span><span class="art-node n3"><x-icon name="check" /></span><span class="art-caption">Cada paso cuenta.</span></div>
        </div>
        <p class="story-footer">EstuFlow · Tu espacio académico</p>
    </aside>
    <main id="contenido" class="auth-main" tabindex="-1">
        <div class="auth-mobile-brand"><x-brand /></div>
        <div class="auth-card">{{ $slot }}</div>
        <p class="auth-footer">Tu cuenta es el comienzo. Lo demás, paso a paso.</p>
    </main>
    @livewireScripts
</body>
</html>
