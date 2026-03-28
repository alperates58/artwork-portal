<p>Merhaba,</p>

<p>Lider Portal'a yeni bir sipariş geldi.</p>

<ul>
    <li><strong>Sipariş No:</strong> {{ $order->order_no }}</li>
    <li><strong>Tedarikçi:</strong> {{ $supplier?->name ?? '-' }}</li>
    <li><strong>Sipariş Tarihi:</strong> {{ optional($order->order_date)->format('d.m.Y') ?? '-' }}</li>
    <li><strong>Satır Sayısı:</strong> {{ $lineCount }}</li>
</ul>

<p>
    Sipariş detayı:
    <a href="{{ $orderUrl }}">{{ $orderUrl }}</a>
</p>

<p>Bu bildirim Lider Portal tarafından otomatik oluşturulmuştur.</p>
