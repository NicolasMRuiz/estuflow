@props(['course', 'row', 'draft' => false])
@php
    $prefix = $draft ? 'entries.'.$course->id.'.' : 'entry.';
    $statusName = $draft ? 'entries['.$course->id.'][status]' : 'status';
    $methodName = $draft ? 'entries['.$course->id.'][approval_method]' : 'approval_method';
    $savedState = old($prefix.'status', $row->status);
    $savedMethod = old($prefix.'approval_method', $row->approval_method ?? 'unspecified');
    $savedState = is_string($savedState) ? $savedState : $row->status;
    $savedMethod = is_string($savedMethod) ? $savedMethod : 'unspecified';
@endphp
<div class="progress-fields" @if($draft) x-data="{ state: @js($savedState), method: @js($savedMethod) }" @else x-data @endif>
    <div><label for="status-{{ $course->id }}">Estado <span class="sr-only">de {{ $course->display_name }}</span></label>
        <select id="status-{{ $course->id }}" name="{{ $statusName }}" @if($draft) x-model="state" @change="if (state !== 'approved') method = 'unspecified'" @else wire:model="status" @change="if ($event.target.value !== 'approved') $wire.approvalMethod = 'unspecified'" @endif data-status-select @error($prefix.'status') aria-invalid="true" aria-describedby="status-error-{{ $course->id }}" @enderror>
            @foreach(\App\Academic\AcademicProgress::STATUSES as $value => $label)<option value="{{ $value }}" @selected($savedState === $value)>{{ $label }}</option>@endforeach
        </select>
        @error($prefix.'status')<p class="field-error" role="alert" id="status-error-{{ $course->id }}">{{ $message }}</p>@enderror
    </div>
    <div class="approval-field" x-cloak x-show="{{ $draft ? 'state' : '$wire.status' }} === 'approved'">
        <label for="method-{{ $course->id }}">Aprobación (opcional) <span class="sr-only">de {{ $course->display_name }}</span></label>
        <select id="method-{{ $course->id }}" name="{{ $methodName }}" @if($draft) x-model="method" @else wire:model="approvalMethod" @endif :disabled="{{ $draft ? 'state' : '$wire.status' }} !== 'approved'" data-method-select @error($prefix.'approval_method') aria-invalid="true" aria-describedby="method-error-{{ $course->id }}" @enderror>
            @foreach(\App\Academic\AcademicProgress::METHODS as $value => $label)
                @if($value !== 'promotion' || $course->accreditation_instance === 'P')<option value="{{ $value }}" @selected($savedMethod === $value)>{{ $label }}</option>@endif
            @endforeach
        </select>
        @error($prefix.'approval_method')<p class="field-error" role="alert" id="method-error-{{ $course->id }}">{{ $message }}</p>@enderror
    </div>
</div>
