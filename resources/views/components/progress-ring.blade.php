@props(['counts'])
<div class="progress-ring" role="img" aria-label="Avance de carrera: {{ $counts['percentage'] }} por ciento, {{ $counts['approved'] }} de {{ $counts['total'] }} materias aprobadas">
    <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="ring-track" cx="60" cy="60" r="53" /><circle class="ring-value" cx="60" cy="60" r="53" pathLength="100" stroke-dasharray="{{ $counts['percentage'] }} 100" transform="rotate(-90 60 60)" /></svg>
    <div><strong>{{ $counts['percentage'] }} %</strong><span>de tu carrera</span></div>
</div>
