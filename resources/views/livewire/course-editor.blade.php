<form method="POST" action="{{ route('career.update', $course->id) }}" wire:submit="save" novalidate>@csrf
    <h2 id="mi-estado">Mi estado actual</h2>
    @foreach(['return_to', 'year', 'filter_status'] as $field)@if(isset($context[$field]))<input type="hidden" name="{{ $field }}" value="{{ $context[$field] }}">@endif @endforeach
    @php($row = (object) ['status' => $status, 'approval_method' => $approvalMethod])
    <x-progress-fields :course="$course" :row="$row" />
    <div class="academic-actions"><x-action type="submit" wire:loading.attr="disabled" wire:target="save">Guardar cambios</x-action><x-action :href="route('career.index', ['year' => $context['year'] ?? '', 'status' => $context['filter_status'] ?? ''])" variant="secondary">Cancelar</x-action><span wire:loading wire:target="save" role="status">Guardando…</span></div>
    <p class="academic-hint">Podés actualizar tu situación aunque todavía te falte declarar alguna correlativa.</p>
</form>
