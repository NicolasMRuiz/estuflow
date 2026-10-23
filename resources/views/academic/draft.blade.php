<x-academic-layout title="Mis materias">
    <x-academic-header title="Tu carrera" />
    <x-academic-steps :step="2" />
    <section class="academic-intro"><span class="eyebrow">A TU RITMO</span><h1>Mis materias</h1><p>{{ \App\Academic\Presentation::career($enrollment->plan) }}</p><p>Podés empezar con todas pendientes y editarlas después. «Final pendiente» significa que ya aprobaste la cursada.</p></section>
    <form method="POST" action="{{ route('academic.save') }}" novalidate data-academic-form data-academic-draft>@csrf
        @foreach($enrollment->plan->courses->groupBy('academic_year') as $year => $courses)
            @php($yearHasError = $courses->contains(fn ($course) => $errors->has('entries.'.$course->id.'.*')))
            <details class="year-section" data-year="{{ $year }}" @if($year === 1 || $yearHasError) open @endif>
                <summary>Año {{ $year }} <span>{{ $courses->count() }} materias</span></summary>
                <div class="year-content">
                    <div class="bulk-controls" data-bulk-controls hidden><div><label for="bulk-{{ $year }}">Aplicar a las {{ $courses->count() }} materias del año {{ $year }}</label><select id="bulk-{{ $year }}" data-bulk-state>@foreach(\App\Academic\AcademicProgress::STATUSES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><x-action variant="secondary" data-bulk-apply>Aplicar al año {{ $year }}</x-action><x-action variant="link" data-bulk-undo hidden>Deshacer</x-action><p class="academic-hint" role="status" data-bulk-notice>Se aplicará al guardar. Las aprobaciones en conjunto quedan sin método especificado.</p></div>
                    @foreach($courses as $course)<section class="course-row"><h3>{{ $course->display_name }}</h3><x-progress-fields :course="$course" :row="$rows[$course->id]" :draft="true" /></section>@endforeach
                </div>
            </details>
        @endforeach
        <div class="academic-actions draft-toolbar"><x-action type="submit" name="next" value="summary" icon="arrow">Continuar</x-action><x-action variant="secondary" type="submit" name="next" value="save">Guardar borrador</x-action><x-action :href="route('academic.choose')" variant="link">Volver</x-action>@if($changing)<x-action type="submit" form="cancel-change" variant="link">Cancelar cambio</x-action>@endif</div>
    </form>
    @if($changing)<form id="cancel-change" method="POST" action="{{ route('academic.cancel') }}">@csrf</form>@endif
</x-academic-layout>
