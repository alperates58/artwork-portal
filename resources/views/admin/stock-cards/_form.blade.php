@php
    $categoryValue = old('category_name', isset($stockCard) ? $stockCard->category?->name : '');
@endphp

<div class="space-y-4">
    <div>
        <label class="label" for="stock_code">Stok Kodu</label>
        <input id="stock_code" name="stock_code" class="input font-mono" value="{{ old('stock_code', $stockCard->stock_code ?? '') }}" required>
    </div>

    <div>
        <label class="label" for="stock_name">Stok Adı</label>
        <input id="stock_name" name="stock_name" class="input" value="{{ old('stock_name', $stockCard->stock_name ?? '') }}" required>
    </div>

    <div>
        <label class="label" for="category_name">Kategori</label>
        <input id="category_name" name="category_name" class="input" list="stock-card-categories" value="{{ $categoryValue }}" required>
        <datalist id="stock-card-categories">
            @foreach($categories as $category)
                <option value="{{ $category->name }}"></option>
            @endforeach
        </datalist>
        <p class="hint">Mevcut kategori yazılırsa yeniden kullanılır, yeni bir ad yazılırsa kontrollü olarak oluşturulur.</p>
    </div>
</div>
