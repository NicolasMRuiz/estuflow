@props(['counts'])
<dl class="academic-state-counts" aria-label="Materias por estado">@foreach(\App\Academic\Presentation::PLURALS as $state => $label)<div><dt>{{ \App\Academic\Presentation::countLabel($state, $counts[$state]) }}</dt><dd>{{ $counts[$state] }}</dd></div>@endforeach</dl>
