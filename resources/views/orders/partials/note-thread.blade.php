<div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
    <div class="flex gap-3">
        <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-100">
            <span class="text-xs font-semibold text-brand-700">{{ strtoupper(mb_substr($note->user->name, 0, 2)) }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="mb-1 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-900">{{ $note->user->name }}</span>
                <span class="text-xs text-slate-400">{{ $note->created_at->format('d.m.Y H:i') }}</span>
                @if($note->updated_at?->ne($note->created_at))
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Düzenlendi</span>
                @endif
            </div>

            <div x-show="editTarget !== {{ $note->id }}">
                <p class="whitespace-pre-wrap text-sm text-slate-700">{{ $note->body }}</p>
            </div>

            <form method="POST" action="{{ route('orders.notes.update', [$order, $note]) }}" class="mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3" x-show="editTarget === {{ $note->id }}" x-cloak>
                @csrf
                @method('PATCH')
                <input type="hidden" name="edit_note_id" value="{{ $note->id }}">
                <label class="label">{{ $note->parent_id ? 'Yanıtı düzenle' : 'Açıklamayı düzenle' }}</label>
                <textarea name="body" rows="3" class="input resize-none" placeholder="Metni güncelleyin...">{{ old('edit_note_id') == $note->id ? old('body') : $note->body }}</textarea>
                @if((string) old('edit_note_id') === (string) $note->id)
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif
                <div class="mt-3 flex items-center justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="editTarget = null">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>

            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                <button type="button" class="font-medium text-brand-700 hover:text-brand-800" @click="replyTo = replyTo === {{ $note->id }} ? null : {{ $note->id }}; showCreate = false; editTarget = null;">
                    Yanıtla
                </button>
                <button type="button" class="font-medium text-slate-600 hover:text-slate-800" @click="editTarget = editTarget === {{ $note->id }} ? null : {{ $note->id }}; replyTo = null; showCreate = false;">
                    Düzenle
                </button>
                @if($line)
                    <span class="text-[11px] text-slate-400">{{ $line->product_code }} / {{ $line->line_no }}</span>
                @endif
            </div>

            @if($note->replies->isNotEmpty())
                <div class="mt-2 space-y-2 border-l-2 border-brand-100 pl-3">
                    @foreach($note->replies as $reply)
                        <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold text-slate-900">{{ $reply->user->name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $reply->created_at->format('d.m.Y H:i') }}</span>
                                <span class="rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-700">Yanıt</span>
                                @if($reply->updated_at?->ne($reply->created_at))
                                    <span class="rounded bg-white px-1.5 py-0.5 text-[10px] text-slate-500">Düzenlendi</span>
                                @endif
                            </div>

                            <div x-show="editTarget !== {{ $reply->id }}">
                                <p class="whitespace-pre-wrap text-sm text-slate-700">{{ $reply->body }}</p>
                            </div>

                            <form method="POST" action="{{ route('orders.notes.update', [$order, $reply]) }}" class="mt-2 rounded-lg border border-dashed border-slate-300 bg-white p-3" x-show="editTarget === {{ $reply->id }}" x-cloak>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="edit_note_id" value="{{ $reply->id }}">
                                <label class="label">Yanıtı düzenle</label>
                                <textarea name="body" rows="2" class="input resize-none" placeholder="Yanıtı güncelleyin...">{{ old('edit_note_id') == $reply->id ? old('body') : $reply->body }}</textarea>
                                @if((string) old('edit_note_id') === (string) $reply->id)
                                    @error('body')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                                <div class="mt-3 flex items-center justify-end gap-2">
                                    <button type="button" class="btn btn-secondary" @click="editTarget = null">Vazgeç</button>
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>

                            <div class="mt-2 flex items-center gap-3 text-[11px]">
                                <button type="button" class="font-medium text-brand-700 hover:text-brand-800" @click="replyTo = {{ $note->id }}; editTarget = null; showCreate = false;">
                                    Konuya yanıtla
                                </button>
                                <button type="button" class="font-medium text-slate-600 hover:text-slate-800" @click="editTarget = editTarget === {{ $reply->id }} ? null : {{ $reply->id }}; replyTo = null; showCreate = false;">
                                    Düzenle
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('orders.notes.store', $order) }}" class="mt-3 rounded-lg border border-dashed border-brand-200 bg-brand-50/40 p-3" x-show="replyTo === {{ $note->id }}" x-cloak>
                @csrf
                @if($line)
                    <input type="hidden" name="purchase_order_line_id" value="{{ $line->id }}">
                @endif
                <input type="hidden" name="parent_id" value="{{ $note->id }}">
                <label class="label">Yanıt</label>
                <textarea name="body" rows="2" class="input resize-none text-sm" placeholder="Yanıtınızı yazın...">{{ old('parent_id') == $note->id ? old('body') : '' }}</textarea>
                @if((string) old('parent_id') === (string) $note->id)
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif
                <div class="mt-3 flex items-center justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="replyTo = null">Vazgeç</button>
                    <button type="submit" class="btn btn-primary">Yanıtla</button>
                </div>
            </form>
        </div>
    </div>
</div>
