@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $buttonClass]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $buttonClass]) }}>
        {{ $slot }}
    </button>
@endif
