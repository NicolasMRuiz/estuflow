@props(['counts'])
<dl class="academic-counts" aria-label="Materias por estado">@foreach(\App\Academic\AcademicProgress::STATUSES as $value => $label)<div><dt>{{ $label }}</dt><dd>{{ $counts[$value] }}</dd></div>@endforeach</dl>
