<x-academic-layout :title="$course->display_name">
    <x-academic-header title="Mi carrera" />
    <div class="detail-back"><a class="academic-link" href="{{ route('career.index', ['year' => $context['year'] ?? '', 'status' => $context['filter_status'] ?? '']) }}">← Volver a Mi carrera</a></div>
    <section class="academic-intro"><h1>{{ $course->display_name }}</h1><p>Año {{ $course->academic_year }} · {{ $course->hours }} horas · {{ $course->field_type === 'G' ? 'Formación general' : 'Formación específica' }}</p><p class="academic-hint">{{ $course->accreditation_instance === 'P' ? 'Se puede aprobar por promoción o por final.' : 'Se aprueba con examen final.' }} {{ $course->free_exam_allowed ? 'Admite examen libre.' : 'No admite examen libre.' }}</p></section>
    <section class="academic-panel"><livewire:course-editor :course-id="$course->id" :context="$context" /></section>
    <section class="academic-panel requirement-context" id="para-cursar">
        <h2>Para cursar</h2><span class="state-tag {{ $guidance['enrollment'] }}">{{ \App\Academic\Advisory::LABELS[$guidance['enrollment']] }}</span>
        @if(count($guidance['columns']['to_enroll_requires_coursework']['conditions']) || count($guidance['columns']['to_enroll_requires_finals']['conditions']))
            @if(count($guidance['columns']['to_enroll_requires_coursework']['conditions']))<h3>Materias con cursada aprobada</h3><x-requirement-list :conditions="$guidance['columns']['to_enroll_requires_coursework']['conditions']" />@endif
            @if(count($guidance['columns']['to_enroll_requires_finals']['conditions']))<h3>Materias con final aprobado</h3><x-requirement-list :conditions="$guidance['columns']['to_enroll_requires_finals']['conditions']" />@endif
        @else<p>No tiene correlativas específicas para cursar.</p>@endif
        <h3>Condición para año {{ $course->academic_year }}</h3>
        @if($course->academic_year === 1)<p class="academic-hint">Para empezar primer año, el instituto pide documentación de inscripción, curso de ingreso y libreta estudiantil. Consultá los plazos con el instituto.</p>
        @else<p>Se requieren al menos dos finales aprobados de año {{ $course->academic_year - 1 }}, uno de formación específica.</p><p class="academic-hint">{{ $guidance['year']['observed'] }}</p><span class="state-tag {{ $guidance['year']['state'] }}">{{ \App\Academic\Advisory::LABELS[$guidance['year']['state']] }}</span>@if($guidance['year']['ambiguous'] ?? 0)<p class="academic-hint">Hay aprobaciones del año anterior cuya equivalencia con un final requiere confirmación del instituto.</p>@endif @endif
    </section>
    <section class="academic-panel requirement-context" id="para-final"><h2>Para rendir el final</h2><span class="state-tag {{ $guidance['final'] }}">{{ \App\Academic\Advisory::LABELS[$guidance['final']] }}</span>
        @if(count($guidance['columns']['to_take_final_requires_finals']['conditions']))<h3>Materias con final aprobado</h3><x-requirement-list :conditions="$guidance['columns']['to_take_final_requires_finals']['conditions']" />@elseif(!count($guidance['specials']))<p>No tiene correlativas específicas de final.</p>@endif
        @foreach($guidance['specials'] as $special)<h3>Las demás materias del plan</h3><p>Para aprobar esta práctica, el plan pide los finales aprobados de las demás materias de la carrera.</p><p class="academic-hint">{{ $special['observed'] }}</p><p class="academic-hint">Por confirmar con el instituto: si se exige al rendir el final o al acreditar la práctica, y cómo se consideran las promociones.</p>@endforeach
    </section>
    <p class="academic-hint">Esta orientación usa tu situación declarada. La inscripción y las condiciones de acreditación se confirman con el instituto.</p>
    <x-plan-source :plan="$enrollment->plan" :course="$course" />
</x-academic-layout>
