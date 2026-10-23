@props(['href' => null, 'variant' => 'primary', 'icon' => null, 'type' => 'button'])
@if($href)
    <a href="{{ $href }}" {{ $attributes->class(['button', $variant]) }}>@if($icon)<x-icon :name="$icon" size="18" />@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['button', $variant]) }}>@if($icon)<x-icon :name="$icon" size="18" />@endif{{ $slot }}</button>
@endif
