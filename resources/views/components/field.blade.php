@props(['name', 'label', 'type' => 'text', 'autocomplete' => '', 'hint' => null])
<div class="form-field">
    <label for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" required
        @if($type !== 'password') value="{{ old($name) }}" @endif
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        @if($hint || $errors->has($name)) aria-describedby="{{ $name }}-help" @endif
        {{ $attributes }}>
    @if($hint || $errors->has($name))
        <p id="{{ $name }}-help" class="{{ $errors->has($name) ? 'field-error' : 'field-hint' }}">{{ $errors->first($name) ?: $hint }}</p>
    @endif
</div>
