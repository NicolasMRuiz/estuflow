<x-academic-layout title="Revisá tu recorrido">
    <x-academic-header title="Tu carrera" />
    <x-academic-steps :step="3" />
    <section class="academic-intro"><span class="eyebrow">LISTO PARA EMPEZAR</span><h1>Revisá tu recorrido</h1><p>{{ \App\Academic\Presentation::career($enrollment->plan) }} · {{ \App\Academic\Presentation::plan($enrollment->plan) }}</p>@if($changing)<p>Al confirmar, ésta será tu carrera activa. Tu progreso anterior se conserva.</p>@endif</section>
    <x-progress-counts :counts="$counts" />
    <form method="POST" action="{{ route('academic.finish') }}" data-academic-form novalidate>@csrf<input type="hidden" name="candidate_id" value="{{ $enrollment->id }}"><div class="academic-actions"><x-action type="submit" icon="check">Confirmar y empezar</x-action><x-action :href="route('academic.draft')" variant="secondary">Volver a editar</x-action></div></form>
    @foreach($enrollment->plan->courses->groupBy('academic_year') as $year => $courses)<details class="year-section"><summary>Año {{ $year }} <span>{{ $courses->count() }} materias</span></summary><div class="year-content"><ul class="course-list">@foreach($courses as $course)<li class="course-summary"><span class="course-name">{{ $course->display_name }}</span><x-status-badge :status="$rows[$course->id]->status" />@if($rows[$course->id]->status === 'approved')<small>{{ \App\Academic\AcademicProgress::METHODS[$rows[$course->id]->approval_method] }}</small>@endif</li>@endforeach</ul></div></details>@endforeach
    @if($changing)<form method="POST" action="{{ route('academic.cancel') }}" class="academic-actions">@csrf<x-action variant="secondary" type="submit">Cancelar cambio</x-action></form>@endif
    <x-plan-source :plan="$enrollment->plan" />
</x-academic-layout>
