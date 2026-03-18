@props([
    'invalid' => false,
])

<input {{ $attributes->merge(['class' => 'input' . ($invalid ? ' error' : '')]) }}>
