<x-academic-layout title="Revisá tu recorrido">
    <x-academic-header title="Configuración académica" />
    <section class="academic-intro"><span class="eyebrow">PASO 3 DE 3 · LISTO PARA EMPEZAR</span><h1>Revisá tu recorrido</h1><p>{{ $enrollment->plan->career->name }} · {{ $enrollment->plan->key }}</p></section>
    <x-progress-counts :counts="$counts" />
    @foreach($enrollment->plan->courses->groupBy('academic_year') as $year => $courses)<section class="year-section"><h2>Año {{ $year }}</h2><ul class="course-list">@foreach($courses as $course)<li><span>{{ $course->code }} · {{ $course->display_name }}</span><strong>{{ \App\Academic\AcademicProgress::STATUSES[$rows[$course->id]->status] }}@if($rows[$course->id]->status === 'approved') · {{ \App\Academic\AcademicProgress::METHODS[$rows[$course->id]->approval_method] }}@endif</strong></li>@endforeach</ul></section>@endforeach
    <form method="POST" action="{{ route('academic.finish') }}">@csrf<label class="check-line"><input type="checkbox" name="confirm" value="1" required>Revisé mi situación. Quiero comenzar con este plan.</label><div class="academic-actions"><a class="button secondary" href="{{ route('academic.draft') }}">Volver a editar</a><button class="button primary" type="submit">Confirmar y ver mi inicio</button></div></form>
</x-academic-layout>
