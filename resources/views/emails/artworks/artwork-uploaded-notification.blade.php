<p>Merhaba,</p>

<p>Lider Portal üzerinde yeni bir artwork revizyonu işlendi.</p>

<ul>
    <li><strong>Sipariş No:</strong> {{ $order->order_no }}</li>
    <li><strong>Tedarikçi:</strong> {{ $supplier?->name ?? '-' }}</li>
    <li><strong>Ürün Kodu:</strong> {{ $line->product_code }}</li>
    <li><strong>Revizyon:</strong> Rev.{{ $revision->revision_no }}</li>
    <li><strong>Yükleyen:</strong> {{ $revision->uploadedBy?->name ?? '-' }}</li>
</ul>

<p>
    Satır detayı:
    <a href="{{ $detailUrl }}">{{ $detailUrl }}</a>
</p>

<p>Bu bildirim Lider Portal tarafından otomatik oluşturulmuştur.</p>
