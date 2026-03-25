@props([
    'title' => null,
])

@php
    $faviconPath = trim((string) config('portal.favicon_path'), '/');
    $faviconUrl = $faviconPath !== '' ? asset($faviconPath) : null;
@endphp

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title . ' — ' : '' }}{{ config('portal.brand_name') }}</title>

@if($faviconUrl)
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
@endif

@include('partials.app-assets')

@stack('styles')
