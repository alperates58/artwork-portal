@if(($galleryItem ?? null) && ! $galleryItem->is_active)
    <x-ui.badge variant="danger">Pasif</x-ui.badge>
@endif
