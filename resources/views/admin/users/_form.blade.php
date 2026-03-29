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
    <label class="label">Sistem Rolü *</label>
    <select name="role" required class="input" id="roleSelect" onchange="toggleRoleFields()">
        @foreach($roles as $role)
            <option value="{{ $role->value }}"
                {{ old('role', $user->role->value ?? '') === $role->value ? 'selected' : '' }}>
                {{ $role->label() }}
            </option>
        @endforeach
    </select>
    @error('role')<p class="err">{{ $message }}</p>@enderror
    <p class="hint">Rol, kullanıcının sistemdeki erişim tabanını belirler. Departman ise organizasyon bilgisidir.</p>
    <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">
        <p><span class="font-semibold text-slate-700">Örnek:</span> Planlama, operasyon veya kalite gibi iç ekipler için ayrı bir sistem rolü yoktur.</p>
        <p class="mt-1">Bu kullanıcı sipariş ve operasyon ekranlarını görecekse genelde <span class="font-semibold text-slate-700">Satın Alma</span> rolü seçilir, sonra doğru departman atanır.</p>
        <p class="mt-1">Daha dar veya farklı erişim gerekiyorsa kullanıcı oluşturulduktan sonra <span class="font-semibold text-slate-700">Yetkiler</span> ekranından özel izin verilir.</p>
    </div>
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
<div id="departmentField" style="display:none">
    <label class="label">Departman</label>
    <select name="department_id" class="input">
        <option value="">— Departman seçin (opsiyonel) —</option>
        @foreach($departments as $dept)
            <option value="{{ $dept->id }}"
                {{ old('department_id', $user->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                {{ $dept->name }}
            </option>
        @endforeach
    </select>
    @error('department_id')<p class="err">{{ $message }}</p>@enderror
    <p class="hint">Departman atanırsa, özel yetki tanımlanmamış kullanıcılar için departman yetkileri geçerli olur.</p>
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
function toggleRoleFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('supplierField').style.display = role === 'supplier' ? 'block' : 'none';
    document.getElementById('departmentField').style.display = (role === 'purchasing' || role === 'graphic') ? 'block' : 'none';
}
toggleRoleFields();
</script>
