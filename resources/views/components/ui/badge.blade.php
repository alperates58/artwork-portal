@props([
    'variant' => 'gray', // success|warning|danger|info|gray
])

@php
    $cls = match($variant) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger'  => 'badge-danger',
        'info'    => 'badge-info',
        default   => 'badge-gray',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$cls}"]) }}>
    {{ $slot }}
</span>
