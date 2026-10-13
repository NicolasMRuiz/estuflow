<x-academic-layout title="Elegí tu carrera">
    <x-academic-header title="Configuración académica" />
    <section class="academic-intro"><span class="eyebrow">PASO 1 DE 3 · TU PUNTO DE PARTIDA</span><h1>{{ $enrollment ? 'Cambiar carrera o plan' : 'Elegí tu carrera' }}</h1><p>Encontrá el plan que corresponde a tu recorrido en el Instituto Tecnológico Beltrán / ISFT N.º 197.</p></section>
    @if($enrollment)<p class="academic-notice">Tu progreso en {{ $enrollment->plan->career->name }} se conserva. Al cambiar, revisarás el borrador del plan elegido antes de confirmarlo.</p>@endif
    <form method="POST" action="{{ route('academic.select') }}" novalidate>
        @csrf
        <fieldset><legend class="academic-legend">Carrera y plan de estudios</legend>
            <div class="plan-grid">@foreach($plans as $plan)
                <label class="plan-option" for="plan-{{ $plan->id }}">
                    <input id="plan-{{ $plan->id }}" type="radio" name="plan_id" value="{{ $plan->id }}" required @checked(old('plan_id') == $plan->id) @error('plan_id') aria-invalid="true" aria-describedby="plan-error" @enderror>
                    <span><strong>{{ $plan->career->name }}</strong><span>Ingreso desde {{ $plan->entry_cohort_from }} · {{ $plan->resolution ? 'Resolución '.$plan->resolution : 'Resolución no indicada en la fuente' }}</span><small>{{ $plan->key }}</small></span>
                </label>
            @endforeach</div>
            @error('plan_id')<p id="plan-error" class="field-error">{{ $message }}</p>@enderror
        </fieldset>
        <p class="academic-hint">Estos documentos no indican fecha final de vigencia. Si tenés dudas sobre el plan que te corresponde, consultá al instituto. Hay tres carreras documentadas; las demás aún no están cargadas.</p>
        @if($enrollment)<label class="check-line"><input type="checkbox" name="confirm_change" value="1" required>Confirmo el cambio de plan activo y la revisión de mi situación.</label>@endif
        <div class="academic-actions"><button class="button primary" type="submit">Continuar con mis materias</button>@if($enrollment?->completed_at)<a class="button secondary" href="{{ route('career.index') }}">Volver a Mi carrera</a>@endif</div>
    </form>
</x-academic-layout>
