@props(['course', 'row', 'draft' => false])
@php
    $prefix = $draft ? 'entries.'.$course->id.'.' : 'entry.';
    $statusName = $draft ? 'entries['.$course->id.'][status]' : 'status';
    $methodName = $draft ? 'entries['.$course->id.'][approval_method]' : 'approval_method';
@endphp
<div class="progress-fields">
    <div><label for="status-{{ $course->id }}">Estado <span class="sr-only">de {{ $course->display_name }}</span></label>
        <select id="status-{{ $course->id }}" name="{{ $statusName }}" @if(!$draft) wire:model="status" @endif @error($prefix.'status') aria-invalid="true" aria-describedby="status-error-{{ $course->id }}" @enderror>
            @foreach(\App\Academic\AcademicProgress::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old($prefix.'status', $row->status) === $value)>{{ $label }}</option>@endforeach
        </select>
        @error($prefix.'status')<p class="field-error" id="status-error-{{ $course->id }}">{{ $message }}</p>@enderror
    </div>
    <div><label for="method-{{ $course->id }}">Método de aprobación <span class="sr-only">de {{ $course->display_name }}</span></label>
        <select id="method-{{ $course->id }}" name="{{ $methodName }}" @if(!$draft) wire:model="approvalMethod" @endif aria-describedby="method-hint-{{ $course->id }}@error($prefix.'approval_method') method-error-{{ $course->id }}@enderror" @error($prefix.'approval_method') aria-invalid="true" @enderror>
            @foreach(\App\Academic\AcademicProgress::METHODS as $value => $label)
                @if($value !== 'promotion' || $course->accreditation_instance === 'P')<option value="{{ $value }}" @selected(old($prefix.'approval_method', $row->approval_method ?? 'unspecified') === $value)>{{ $label }}</option>@endif
            @endforeach
        </select>
        <small id="method-hint-{{ $course->id }}">Sólo se guarda para el estado Aprobada. Podés dejarlo sin especificar.</small>
        @error($prefix.'approval_method')<p class="field-error" id="method-error-{{ $course->id }}">{{ $message }}</p>@enderror
    </div>
</div>
