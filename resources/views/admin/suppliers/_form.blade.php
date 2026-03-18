<div>
    <label class="label">Tedarikçi Adı *</label>
    <x-ui.input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}" required :invalid="$errors->has('name')" />
    @error('name')<p class="err">{{ $message }}</p>@enderror
</div>
<div>
    <label class="label">Kod *</label>
    <x-ui.input type="text" name="code" value="{{ old('code', $supplier->code ?? '') }}" required placeholder="TED-001" :invalid="$errors->has('code')" />
    @error('code')<p class="err">{{ $message }}</p>@enderror
</div>
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="label">E-posta</label>
        <x-ui.input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" :invalid="$errors->has('email')" />
        @error('email')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label">Telefon</label>
        <x-ui.input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" :invalid="$errors->has('phone')" />
        @error('phone')<p class="err">{{ $message }}</p>@enderror
    </div>
</div>
<div>
    <label class="label">Adres</label>
    <textarea name="address" rows="2" class="input resize-none">{{ old('address', $supplier->address ?? '') }}</textarea>
    @error('address')<p class="err">{{ $message }}</p>@enderror
</div>
<div>
    <label class="label">Notlar</label>
    <textarea name="notes" rows="2" class="input resize-none">{{ old('notes', $supplier->notes ?? '') }}</textarea>
    @error('notes')<p class="err">{{ $message }}</p>@enderror
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1"
           {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}
           class="rounded border-slate-300 text-blue-600">
    <label for="is_active" class="text-sm text-slate-700">Aktif</label>
</div>
