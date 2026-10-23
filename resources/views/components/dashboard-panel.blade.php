@props(['title', 'eyebrow', 'icon', 'tone' => 'cream', 'id'])
<section class="panel" aria-labelledby="{{ $id }}">
    <div class="panel-heading"><div><span class="eyebrow">{{ $eyebrow }}</span><h2 id="{{ $id }}">{{ $title }}</h2></div><span class="icon-tile {{ $tone }}"><x-icon :name="$icon" /></span></div>
    {{ $slot }}
</section>
