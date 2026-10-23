@props(['inline' => []])
@foreach($errors->getMessages() as $field => $messages)
    @unless(\Illuminate\Support\Str::is($inline, $field))<p class="error-summary" role="alert" tabindex="-1" data-academic-error>{{ $messages[0] }}</p>@endunless
@endforeach
