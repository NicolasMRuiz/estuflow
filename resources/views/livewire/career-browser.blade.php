<div>
    <section class="academic-intro career-intro"><div class="career-title-row"><div><span class="eyebrow">TU RECORRIDO</span><h1>Mi carrera</h1></div><x-action :href="route('academic.choose')" variant="secondary" icon="route">Cambiar carrera</x-action></div><h2>{{ \App\Academic\Presentation::career($enrollment->plan) }}</h2><p>{{ \App\Academic\Presentation::plan($enrollment->plan) }}</p></section>
    @if($candidate)<p class="academic-notice">Tenés un cambio de carrera en borrador. <a class="academic-link" href="{{ route('academic.draft') }}">Retomar cambio →</a></p>@endif
    <x-progress-counts :counts="$counts" />
    <form class="academic-filters" method="GET" action="{{ route('career.index') }}"><div><label for="year-filter">Año</label><select name="year" id="year-filter"><option value="">Todos</option>@foreach([1,2,3] as $option)<option value="{{ $option }}" @selected($year == $option)>Año {{ $option }}</option>@endforeach</select></div><div><label for="status-filter">Estado</label><select name="status" id="status-filter"><option value="">Todos</option>@foreach(\App\Academic\AcademicProgress::STATUSES as $value => $label)<option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>@endforeach</select></div><x-action variant="secondary" type="submit">Aplicar filtros</x-action><x-action :href="route('career.index')" variant="link">Limpiar filtros</x-action></form>
    <p class="academic-notice" role="status">{{ $notice }}</p>
    <p id="resultados" tabindex="-1" class="academic-hint">{{ $courses->count() }} {{ $courses->count() === 1 ? 'materia encontrada' : 'materias encontradas' }}. Final pendiente significa cursada aprobada.</p>
    @forelse($courses->groupBy('academic_year') as $academicYear => $group)<section class="year-section"><div class="year-content"><h2 class="list-year-title">Año {{ $academicYear }}</h2><ul class="course-list">@foreach($group as $course)
        @php($context = ['course' => $course->id, 'return_to' => 'career', 'year' => $year, 'filter_status' => $statusFilter])
        <li id="materia-{{ $course->id }}" wire:key="course-{{ $course->id }}">
            <div class="course-summary"><a class="course-name" href="{{ route('career.detail', $context) }}">{{ $course->display_name }}</a><x-status-badge :status="$rows[$course->id]->status" /><div class="row-actions"><x-action :href="route('career.detail', $context)" variant="link" aria-label="Ver detalle de {{ $course->display_name }}">Ver detalle</x-action><x-action :href="route('career.detail', $context) . '#mi-estado'" variant="secondary" wire:click.prevent="edit({{ $course->id }})" data-edit-course wire:loading.attr="inert" aria-label="Editar estado de {{ $course->display_name }}">Editar estado</x-action></div></div>
            @if($editingCourseId === $course->id)
                <form class="inline-editor" method="POST" action="{{ route('career.update', $course->id) }}" wire:submit="save" novalidate>@csrf
                    <input type="hidden" name="return_to" value="career"><input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="filter_status" value="{{ $statusFilter }}">
                    @php($row = (object) ['status' => $status, 'approval_method' => $approvalMethod])
                    <x-progress-fields :course="$course" :row="$row" />
                    <div class="academic-actions"><x-action type="submit" wire:loading.attr="disabled" >Guardar cambios</x-action><x-action variant="secondary" wire:click="cancel" wire:loading.attr="disabled">Cancelar</x-action><span wire:loading wire:target="save" role="status">Guardando…</span></div>
                </form>
            @endif
        </li>
    @endforeach</ul></div></section>@empty<p class="academic-notice">No hay materias con estos filtros. Probá con otro año o estado.</p>@endforelse
    <x-plan-source :plan="$enrollment->plan" />
</div>
