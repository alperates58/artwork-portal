<div>
    <label class="label">Tedarikçi Adı *</label>
    <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}" required class="input">
    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
<div>
    <label class="label">Kod *</label>
    <input type="text" name="code" value="{{ old('code', $supplier->code ?? '') }}" required class="input" placeholder="TED-001">
    @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="label">E-posta</label>
        <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" class="input">
    </div>
    <div>
        <label class="label">Telefon</label>
        <input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" class="input">
    </div>
</div>
<div>
    <label class="label">Adres</label>
    <textarea name="address" rows="2" class="input resize-none">{{ old('address', $supplier->address ?? '') }}</textarea>
</div>
<div>
    <label class="label">Notlar</label>
    <textarea name="notes" rows="2" class="input resize-none">{{ old('notes', $supplier->notes ?? '') }}</textarea>
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1"
           {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}
           class="rounded border-slate-300 text-blue-600">
    <label for="is_active" class="text-sm text-slate-700">Aktif</label>
</div>
