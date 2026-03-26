@php
    $sizeClass = $sizeClass ?? 'h-16 w-16';
    $roundedClass = $roundedClass ?? 'rounded-2xl';
@endphp

@if($artworkGallery->is_image)
    <img
        src="{{ route('artworks.gallery.preview', $artworkGallery) }}"
        alt="{{ $artworkGallery->display_name }}"
        class="{{ $sizeClass }} {{ $roundedClass }} object-cover ring-1 ring-slate-200"
        loading="lazy"
    >
@else
    <div class="{{ $sizeClass }} {{ $roundedClass }} flex items-center justify-center bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-200">
        @if($artworkGallery->file_type_icon === 'pdf')
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
                <path d="M14 3.75v4h4"/>
                <path d="M8 15.25h8M8 18h5"/>
            </svg>
        @elseif($artworkGallery->file_type_icon === 'design')
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M4.75 6.75A2.75 2.75 0 0 1 7.5 4h9A2.75 2.75 0 0 1 19.25 6.75v10.5A2.75 2.75 0 0 1 16.5 20h-9a2.75 2.75 0 0 1-2.75-2.75Z"/>
                <path d="M8 16l2.5-3 2 2 3.5-5"/>
                <path d="M8 9.5h.01"/>
            </svg>
        @else
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
                <path d="M14 3.75v4h4"/>
            </svg>
        @endif
    </div>
@endif
