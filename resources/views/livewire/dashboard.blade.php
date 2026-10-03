<div>
    <header class="dashboard-header">
        <div class="breadcrumb"><span>Mi espacio</span><span aria-hidden="true">/</span><strong>Inicio</strong></div>
        <form method="POST" action="{{ route('logout') }}" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <button type="submit" class="logout-button" :disabled="busy"><x-icon name="logout" size="19" /><span x-text="busy ? 'Saliendo…' : 'Cerrar sesión'">Cerrar sesión</span></button>
        </form>
    </header>
    <section class="welcome">
        <div><span class="eyebrow">UN PASO A LA VEZ</span><h1>Hola, <span>{{ $user->name }}</span><span class="greeting-dot">.</span></h1><p>Este es tu espacio para ordenar lo que viene.</p></div>
        <div class="account-badge"><span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><div><strong>Tu cuenta personal</strong><span>El comienzo de tu camino</span></div></div>
    </section>
    <section class="intro-strip" aria-label="Bienvenida a EstuFlow"><span class="intro-icon"><x-icon name="spark" size="24" /></span><div><strong>Un nuevo comienzo, a tu ritmo.</strong><p>Tu cuenta ya está creada. Las herramientas académicas llegarán en las próximas entregas.</p></div><span class="intro-tag">PRIMER PASO</span></section>
    <div class="dashboard-grid">
        <section class="panel progress-panel" aria-labelledby="avance-titulo">
            <div class="panel-heading"><div><span class="eyebrow">TU RECORRIDO</span><h2 id="avance-titulo">Avance de carrera</h2></div><span class="icon-tile mint"><x-icon name="book" /></span></div>
            <div class="progress-empty"><div class="progress-ring"><div><strong>Sin datos</strong><span>avance de carrera</span></div></div><div class="progress-copy"><span class="status-pill">Todavía sin carrera</span><h3>Todo camino tiene<br>un punto de partida.</h3><p>Aún no tenés una carrera cargada. Cuando esta sección esté disponible, podrás ver tu avance acá.</p></div></div>
            <div class="panel-foot"><span class="small-dot" aria-hidden="true"></span>Mi carrera estará disponible próximamente.</div>
        </section>
        <section class="panel" aria-labelledby="actividades-titulo"><div class="panel-heading"><div><span class="eyebrow">LO QUE VIENE</span><h2 id="actividades-titulo">Próximas actividades</h2></div><span class="icon-tile cream"><x-icon name="calendar" /></span></div><div class="empty-state"><span class="empty-symbol"><x-icon name="calendar" size="32" /></span><h3>Tu agenda está en blanco</h3><p>Todavía no hay actividades académicas.<br>Por ahora, disfrutá este nuevo comienzo.</p><span class="pending-caption">Calendario · Próximamente</span></div></section>
        <section class="panel" aria-labelledby="materias-titulo"><div class="panel-heading"><div><span class="eyebrow">PASO A PASO</span><h2 id="materias-titulo">Materias en curso</h2></div><span class="icon-tile cream"><x-icon name="layers" /></span></div><div class="empty-state compact"><span class="empty-symbol"><x-icon name="book" size="32" /></span><h3>Un espacio para tus materias</h3><p>No hay materias cargadas todavía. En una próxima entrega podrás organizar tu cursada.</p><span class="pending-caption">Materias · Próximamente</span></div></section>
        <section class="panel" aria-labelledby="correlativas-titulo"><div class="panel-heading"><div><span class="eyebrow">CONECTÁ TU CAMINO</span><h2 id="correlativas-titulo">Correlativas</h2></div><span class="icon-tile coral"><x-icon name="route" /></span></div><div class="empty-state compact"><span class="empty-symbol"><x-icon name="route" size="32" /></span><h3>Cada materia, una conexión</h3><p>Sin carrera ni materias, no hay correlativas para mostrar. Acá verás cómo se conectan tus próximos pasos.</p><span class="pending-caption">Correlativas · Próximamente</span></div></section>
    </div>
    <footer class="dashboard-footer"><span>Hecho para avanzar con claridad.</span><span>EstuFlow <span aria-hidden="true">↗</span></span></footer>
</div>
