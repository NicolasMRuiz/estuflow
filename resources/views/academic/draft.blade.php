<x-academic-layout title="Tu situación inicial">
    <x-academic-header title="Configuración académica" />
    <section class="academic-intro"><span class="eyebrow">PASO 2 DE 3 · A TU RITMO</span><h1>Contanos dónde estás</h1><p>{{ $enrollment->plan->career->name }} · Ingreso desde {{ $enrollment->plan->entry_cohort_from }}</p><p>Guardá tu situación actual. También podés comenzar con todas las materias pendientes.</p></section>
    <form method="POST" action="{{ route('academic.save') }}" novalidate>@csrf
        @foreach($enrollment->plan->courses->groupBy('academic_year') as $year => $courses)
            <fieldset class="year-section"><legend>Año {{ $year }} <span>{{ $courses->count() }} materias</span></legend>
                @foreach($courses as $course)<section class="course-row"><h2>{{ $course->code }} · {{ $course->display_name }}</h2><p class="academic-hint">{{ $course->hours }} horas · {{ $course->field_type }} · {{ $course->accreditation_instance }}</p><x-progress-fields :course="$course" :row="$rows[$course->id]" :draft="true" /></section>@endforeach
            </fieldset>
        @endforeach
        <div class="academic-actions"><button class="button secondary" type="submit" name="next" value="save">Guardar borrador</button><button class="button primary" type="submit" name="next" value="summary">Revisar resumen</button><a href="{{ route('academic.choose') }}">Elegir otro plan</a></div>
    </form>
</x-academic-layout>
