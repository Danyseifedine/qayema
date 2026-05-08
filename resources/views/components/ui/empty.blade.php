@props([
    'title'       => '',
    'description' => '',
    'icon'        => null,
])

<div class="empty-state">
    @if ($icon)
        <div class="empty-state-icon">{!! $icon !!}</div>
    @endif

    <h3 class="empty-state-title">{{ $title }}</h3>

    @if ($description)
        <p class="empty-state-desc">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>
