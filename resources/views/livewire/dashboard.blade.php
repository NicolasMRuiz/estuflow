<div>
    <x-academic-header title="Inicio" />
    <section class="welcome">
        <div><span class="eyebrow">UN PASO A LA VEZ</span><h1>Hola, <span>{{ $user->name }}</span><span class="greeting-dot">.</span></h1><p>Este es tu espacio para ordenar lo que viene.</p></div>
        <div class="account-badge"><span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><div><strong>Tu cuenta personal</strong><span>Tu camino, a tu ritmo</span></div></div>
    </section>
    <section class="intro-strip" aria-label="Tu carrera"><span class="intro-icon"><x-icon name="spark" size="24" /></span><div><strong>{{ \App\Academic\Presentation::career($enrollment->plan) }}</strong><p>{{ \App\Academic\Presentation::plan($enrollment->plan) }}</p></div><a class="intro-tag" href="{{ route('career.index') }}">MI CARRERA <span aria-hidden="true">↗</span></a></section>
    <div class="dashboard-grid">
        <x-dashboard-panel title="Avance de carrera" eyebrow="TU RECORRIDO" icon="book" tone="mint" id="avance-titulo">
            <div class="progress-empty"><x-progress-ring :counts="$counts" /><div class="progress-copy"><span class="status-pill">Cada paso cuenta</span><h3>{{ $counts['approved'] }} de {{ $counts['total'] }} materias aprobadas</h3><p>Lo que ya lograste, a partir de tu situación actual.</p><a class="academic-link" href="{{ route('career.index') }}">Ver Mi carrera <span aria-hidden="true">→</span></a></div></div>
            <div class="panel-foot state-totals" aria-label="Materias por estado">@foreach(\App\Academic\Presentation::PLURALS as $state => $label)<span><strong>{{ $counts[$state] }}</strong> {{ \App\Academic\Presentation::countLabel($state, $counts[$state]) }}</span>@endforeach</div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Próximas actividades" eyebrow="LO QUE VIENE" icon="calendar" id="actividades-titulo">
            <div class="empty-state"><span class="empty-symbol"><x-icon name="calendar" size="32" /></span><h3>Tu agenda está en blanco</h3><p>Acá habrá lugar para organizar lo que viene.</p><span class="pending-caption">Calendario · Próximamente</span></div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Materias en curso" eyebrow="PASO A PASO" icon="layers" id="materias-titulo">
            @if($counts['in_progress'])<div class="dashboard-list"><p>{{ $counts['in_progress'] === 1 ? 'Tenés 1 materia en curso.' : 'Tenés '.$counts['in_progress'].' materias en curso.' }}</p><ul>@foreach($currentCourses as $course)<li><a href="{{ route('career.detail', $course->id) }}">{{ $course->display_name }}<x-icon name="arrow" size="17" /></a></li>@endforeach</ul><a class="academic-link" href="{{ route('career.index', ['status' => 'in_progress']) }}">Ver todas ({{ $counts['in_progress'] }}) <span aria-hidden="true">→</span></a></div>
            @else<div class="empty-state compact"><span class="empty-symbol"><x-icon name="book" size="32" /></span><h3>¿Qué estás cursando?</h3><p>Actualizá el estado de tus materias para encontrarlas acá.</p><a class="academic-link" href="{{ route('career.index') }}">Actualizar mis materias →</a></div>@endif
        </x-dashboard-panel>
        <x-dashboard-panel title="Correlativas" eyebrow="CONECTÁ TU CAMINO" icon="route" tone="coral" id="correlativas-titulo">
            @if($suggestedCourses->isNotEmpty())<div class="dashboard-list"><p>Explorá los requisitos de tus próximas materias.</p><ul>@foreach($suggestedCourses as $course)<li><a href="{{ route('career.detail', $course->id) }}#para-cursar">{{ $course->display_name }}<x-icon name="arrow" size="17" /></a></li>@endforeach</ul><a class="academic-link" href="{{ route('career.index', ['status' => 'pending']) }}">Ver las materias pendientes →</a></div>
            @else<div class="empty-state compact"><span class="empty-symbol"><x-icon name="check" size="32" /></span><h3>No tenés materias pendientes</h3><p>Podés consultar los requisitos de cualquier materia en tu carrera.</p><a class="academic-link" href="{{ route('career.index') }}">Explorar Mi carrera →</a></div>@endif
        </x-dashboard-panel>
    </div>
    <footer class="dashboard-footer"><span>Hecho para avanzar con claridad.</span><span>EstuFlow <span aria-hidden="true">↗</span></span></footer>
</div>
