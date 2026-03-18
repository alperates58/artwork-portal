<div>
    <label class="label">Ad Soyad *</label>
    <x-ui.input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required :invalid="$errors->has('name')" />
    @error('name')<p class="err">{{ $message }}</p>@enderror
</div>
<div>
    <label class="label">E-posta *</label>
    <x-ui.input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required :invalid="$errors->has('email')" />
    @error('email')<p class="err">{{ $message }}</p>@enderror
</div>
<div>
    <label class="label">Rol *</label>
    <select name="role" required class="input" id="roleSelect" onchange="toggleSupplier()">
        @foreach($roles as $role)
            <option value="{{ $role->value }}"
                {{ old('role', $user->role->value ?? '') === $role->value ? 'selected' : '' }}>
                {{ $role->label() }}
            </option>
        @endforeach
    </select>
    @error('role')<p class="err">{{ $message }}</p>@enderror
</div>
<div id="supplierField" style="display:none">
    <label class="label">Tedarikçi Firma *</label>
    <select name="supplier_id" class="input">
        <option value="">Seçin...</option>
        @foreach($suppliers as $id => $name)
            <option value="{{ $id }}"
                {{ old('supplier_id', $user->supplier_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </select>
    @error('supplier_id')<p class="err">{{ $message }}</p>@enderror
</div>
<div>
    <label class="label">Şifre {{ isset($isCreate) && !$isCreate ? '(boş bırakılırsa değişmez)' : '*' }}</label>
    <input type="password" name="password" {{ isset($isCreate) && $isCreate ? 'required' : '' }}
           minlength="8" class="input" placeholder="En az 8 karakter">
    @error('password')<p class="err">{{ $message }}</p>@enderror
</div>
<div>
    <label class="label">Şifre Tekrar</label>
    <input type="password" name="password_confirmation" class="input">
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1"
           {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
           class="rounded border-slate-300 text-blue-600">
    <label for="is_active" class="text-sm text-slate-700">Aktif</label>
</div>
<script>
function toggleSupplier() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('supplierField').style.display = role === 'supplier' ? 'block' : 'none';
}
toggleSupplier();
</script>
