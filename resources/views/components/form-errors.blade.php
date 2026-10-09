@php
    $fieldLabels = ['name' => 'Tu nombre', 'email' => 'Email', 'password' => 'Contraseña', 'password_confirmation' => 'Confirmar contraseña'];
    $fieldErrors = collect($errors->getMessages())->only(array_keys($fieldLabels));
    $generalErrors = collect($errors->getMessages())->except(array_keys($fieldLabels));
    $showSummary = $fieldErrors->count() > 1;
@endphp
<div id="form-errors" class="error-summary" role="alert" tabindex="-1" data-form-errors @if($generalErrors->isEmpty() && !$showSummary) hidden @endif>
    @foreach($generalErrors as $messages)
        <p data-general-error>{{ $messages[0] }}</p>
    @endforeach
    <div data-field-summary @if(!$showSummary) hidden @endif>
        <p>Revisá los campos indicados:</p>
        <ul>
            @foreach($fieldErrors as $field => $messages)
                <li data-summary-for="{{ $field }}"><a href="#{{ $field }}">{{ $fieldLabels[$field] }}: {{ $messages[0] }}</a></li>
            @endforeach
        </ul>
    </div>
</div>
