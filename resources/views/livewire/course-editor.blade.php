<form method="POST" action="{{ route('career.update', $course->id) }}" wire:submit="save" novalidate>@csrf
    <h2>Mi estado actual</h2>
    @php($row = (object) ['status' => $status, 'approval_method' => $approvalMethod])
    <x-progress-fields :course="$course" :row="$row" />
    <div class="academic-actions"><button class="button primary" type="submit" wire:loading.attr="disabled">Guardar estado</button></div>
    <p class="academic-hint">Podés guardar tu situación aunque falten correlativas en tu declaración.</p>
</form>
