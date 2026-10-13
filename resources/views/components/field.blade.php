@props(['name', 'label', 'type' => 'text', 'autocomplete' => '', 'hint' => null, 'value' => null, 'required' => true])
@php
    $hasError = $errors->has($name);
    $fieldValue = old($name, $value);
    $fieldValue = is_string($fieldValue) ? $fieldValue : '';
    $describedBy = array_filter([$hint ? $name.'-hint' : null, $hasError ? $name.'-error' : null]);
@endphp
<div class="form-field" data-field="{{ $name }}">
    <label for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" @required($required)
        @if($type !== 'password') value="{{ $fieldValue }}" @endif
        data-label="{{ $label }}" aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if($describedBy) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
        {{ $attributes }}>
    @if($hint)<p id="{{ $name }}-hint" class="field-hint">{{ $hint }}</p>@endif
    <p id="{{ $name }}-error" class="field-error" data-error-for="{{ $name }}" aria-live="polite" @if(!$hasError) hidden @endif>{{ $errors->first($name) }}</p>
</div>
