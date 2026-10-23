@props(['step'])
<ol class="academic-steps" aria-label="Pasos de configuración">
    @foreach([1 => 'Tu carrera', 2 => 'Mis materias', 3 => 'Resumen'] as $number => $label)
        <li @if($step === $number) aria-current="step" @endif class="{{ $number <= $step ? 'reached' : '' }}"><span>{{ $number }}</span>{{ $label }}</li>
    @endforeach
</ol>
