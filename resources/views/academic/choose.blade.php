<x-academic-layout title="Elegí tu carrera">
    <x-academic-header title="Tu carrera" :inline-errors="['plan_id']" />
    <x-academic-steps :step="1" />
    <section class="academic-intro"><span class="eyebrow">TU PUNTO DE PARTIDA</span><h1>{{ $enrollment?->completed_at ? 'Cambiar carrera' : 'Elegí tu carrera' }}</h1><p>{{ $enrollment?->completed_at ? 'Tu carrera actual se conserva hasta que confirmes el cambio.' : 'Elegí la carrera y el plan que corresponden a tu recorrido.' }}</p></section>
    @if($candidate)<p class="academic-notice">Tenés un borrador de {{ \App\Academic\Presentation::career($candidate->plan) }}. <a href="{{ route('academic.draft') }}" class="academic-link">Retomar borrador →</a></p>@endif
    <form method="POST" action="{{ route('academic.select') }}" novalidate data-academic-form>
        @csrf
        <fieldset><legend class="academic-legend">Carreras disponibles</legend>
            <div class="plan-grid">@foreach($plans as $plan)
                <div class="plan-card"><label class="plan-option" for="plan-{{ $plan->id }}">
                    <input id="plan-{{ $plan->id }}" type="radio" name="plan_id" value="{{ $plan->id }}" required @checked(old('plan_id') == $plan->id) @error('plan_id') aria-invalid="true" aria-describedby="plan-error" @enderror>
                    <span><strong>{{ \App\Academic\Presentation::career($plan) }}</strong><small>3 años · {{ $plan->courses_count }} materias</small><small>{{ \App\Academic\Presentation::plan($plan) }}</small></span>
                </label><x-plan-source :plan="$plan" /></div>
            @endforeach</div>
            @error('plan_id')<p id="plan-error" role="alert" class="field-error">{{ $message }}</p>@enderror
        </fieldset>
        <div class="academic-actions"><x-action type="submit" icon="arrow">Continuar</x-action>@if($enrollment?->completed_at)<x-action type="submit" form="cancel-change" variant="secondary">Cancelar cambio</x-action>@endif</div>
    </form>
    @if($enrollment?->completed_at)<form id="cancel-change" method="POST" action="{{ route('academic.cancel') }}">@csrf</form>@endif
</x-academic-layout>
