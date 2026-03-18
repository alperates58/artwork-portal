@props([
    'variant' => 'primary', // primary|secondary
    'type' => 'button',
])

@php
    $variantClass = match($variant) {
        'secondary' => 'btn-secondary',
        default => 'btn-primary',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "btn {$variantClass}"]) }}
>
    {{ $slot }}
</button>
