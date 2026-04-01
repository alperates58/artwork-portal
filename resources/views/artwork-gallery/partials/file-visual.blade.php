@php
    $sizeClass    = $sizeClass    ?? 'h-16 w-16';
    $roundedClass = $roundedClass ?? 'rounded-2xl';
    $iconType     = $artworkGallery->file_type_icon; // 'image' | 'pdf' | 'design' | 'file'
@endphp

<div class="relative">
    @if($artworkGallery->has_preview)
        <img
            src="{{ route('artworks.gallery.preview', $artworkGallery, false) }}"
            alt="{{ $artworkGallery->display_name }}"
            class="{{ $sizeClass }} {{ $roundedClass }} object-cover ring-1 ring-slate-200 bg-slate-50"
            loading="lazy"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        {{-- Fallback ikonu (img yüklenemezse gösterilir) --}}
        <div class="{{ $sizeClass }} {{ $roundedClass }} hidden items-center justify-center bg-slate-100 text-slate-400 ring-1 ring-inset ring-slate-200">
            @include('artwork-gallery.partials.file-icon', ['iconType' => $iconType, 'sizeClass' => $sizeClass])
        </div>
    @else
        <div class="{{ $sizeClass }} {{ $roundedClass }} flex items-center justify-center bg-slate-100 text-slate-400 ring-1 ring-inset ring-slate-200">
            @include('artwork-gallery.partials.file-icon', ['iconType' => $iconType, 'sizeClass' => $sizeClass])
        </div>
    @endif

    <div class="absolute left-2 top-2">
        @include('artworks.partials.passive-gallery-badge', ['galleryItem' => $artworkGallery])
    </div>
</div>
