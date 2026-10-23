@props(['conditions'])
<ul class="course-list">@foreach($conditions as $condition)<li><a class="academic-link" href="{{ route('career.detail', $condition['course']->id) }}">{{ $condition['course']->display_name }}</a><span class="state-tag {{ $condition['state'] }}">{{ \App\Academic\Advisory::LABELS[$condition['state']] }}</span></li>@endforeach</ul>
@if(collect($conditions)->contains('state', 'uncertain'))<p class="academic-hint">Por confirmar: el plan no aclara si promoción equivale a final. Si no especificaste el método de aprobación, tampoco podemos confirmarlo.</p>@endif
