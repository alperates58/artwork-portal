@props([
    'variant' => 'info', // success|danger|warning|info
])

@php
    $map = [
        'success' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-800', 'icon' => 'text-emerald-600'],
        'danger'  => ['bg' => 'bg-red-50',     'border' => 'border-red-200',     'text' => 'text-red-800',     'icon' => 'text-red-600'],
        'warning' => ['bg' => 'bg-amber-50',   'border' => 'border-amber-200',   'text' => 'text-amber-800',   'icon' => 'text-amber-600'],
        'info'    => ['bg' => 'bg-blue-50',    'border' => 'border-blue-200',    'text' => 'text-blue-800',    'icon' => 'text-blue-600'],
    ];
    $c = $map[$variant] ?? $map['info'];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border {$c['bg']} {$c['border']} {$c['text']} text-sm"]) }}>
    <svg class="w-4 h-4 flex-shrink-0 mt-0.5 {{ $c['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div class="min-w-0">
        {{ $slot }}
    </div>
</div>
