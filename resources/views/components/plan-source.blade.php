@props(['plan', 'course' => null])
<details class="source-details"><summary>Datos del plan</summary>
    <p>{{ $plan->career->name }} · {{ \App\Academic\Presentation::plan($plan) }}</p>
    @if($plan->resolution)<p>Resolución {{ $plan->resolution }}</p>@endif
    <p>Referencia: {{ $plan->key }}. La vigencia de tu plan se confirma con el instituto.</p>
    <p>Fuente: {{ $plan->source_data['source']['filename'] }}</p>
    @if($course)<p>Materia {{ $course->code }} · Página {{ $course->source_data['source_page'] }}, fila {{ $course->source_data['source_row'] }}. Nombre en la fuente: {{ $course->source_name }}.</p>
        @foreach($course->specialRules as $special)<p>{{ $special->source_data['source_note'] }}</p>@endforeach
    @endif
</details>
