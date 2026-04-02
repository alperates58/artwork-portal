{{--
    Mention-aware textarea.
    Props: name, rows (default 3), placeholder, value, inputClass
--}}
<div x-data="mentionPicker()" class="relative">
    <textarea
        x-ref="ta"
        name="{{ $name }}"
        rows="{{ $rows ?? 3 }}"
        class="{{ $inputClass ?? 'input resize-none' }}"
        placeholder="{{ $placeholder ?? '' }}"
        @keyup="onKeyup($event)"
        @keydown="onKeydown($event)"
    >{{ $value ?? '' }}</textarea>

    <div x-show="show" x-cloak
         class="absolute left-0 z-50 mt-1 max-h-52 w-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl">
        <template x-for="(user, idx) in filtered" :key="user.id">
            <button type="button"
                    class="flex w-full items-center gap-2 px-3 py-2 text-sm text-left"
                    :class="idx === cursorIndex ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-700 hover:bg-slate-50'"
                    @mousedown.prevent="select(user)">
                <span class="inline-flex h-6 w-6 flex-none items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-600" x-text="user.name.charAt(0)"></span>
                <span x-text="user.name"></span>
            </button>
        </template>
    </div>
</div>
