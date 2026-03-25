<p>Merhaba,</p>

<p>Lider Portal'a yeni bir siparis geldi.</p>

<ul>
    <li><strong>Siparis No:</strong> {{ $order->order_no }}</li>
    <li><strong>Tedarikci:</strong> {{ $supplier?->name ?? '-' }}</li>
    <li><strong>Siparis Tarihi:</strong> {{ optional($order->order_date)->format('d.m.Y') ?? '-' }}</li>
    <li><strong>Satir Sayisi:</strong> {{ $lineCount }}</li>
</ul>

<p>
    Siparis detayi:
    <a href="{{ $orderUrl }}">{{ $orderUrl }}</a>
</p>

<p>Bu bildirim Lider Portal tarafindan otomatik olusturulmustur.</p>
