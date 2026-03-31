@php $iconType = $iconType ?? 'file'; @endphp

@if($iconType === 'pdf')
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v4h4"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 15.25h8M8 18h5"/>
    </svg>
@elseif($iconType === 'design')
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75A2.75 2.75 0 0 1 7.5 4h9A2.75 2.75 0 0 1 19.25 6.75v10.5A2.75 2.75 0 0 1 16.5 20h-9a2.75 2.75 0 0 1-2.75-2.75Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16l2.5-3 2 2 3.5-5"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9.5h.01"/>
    </svg>
@elseif($iconType === 'image')
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-10h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
    </svg>
@else
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4 4V20.25A1.75 1.75 0 0 1 16.25 22h-8.5A1.75 1.75 0 0 1 6 20.25v-14.5A1.75 1.75 0 0 1 7.75 4Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v4h4"/>
    </svg>
@endif
