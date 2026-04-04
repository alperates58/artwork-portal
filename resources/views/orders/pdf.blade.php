<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Sipariş {{ $order->order_no }}</title>
<style>
    @page {
        margin: 18mm 16mm 22mm 16mm;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9pt;
        color: #334155;
        background: #ffffff;
        line-height: 1.4;
    }

    /* ─── HEADER ─── */
    .header {
        margin-bottom: 20px;
    }
    .header-bar {
        background: #1d4ed8;
        height: 5px;
        border-radius: 2px;
        margin-bottom: 12px;
    }
    .header-table {
        width: 100%;
        display: table;
    }
    .header-left {
        display: table-cell;
        vertical-align: bottom;
        width: 55%;
    }
    .header-right {
        display: table-cell;
        vertical-align: bottom;
        width: 45%;
        text-align: right;
    }
    .brand-name {
        font-size: 20pt;
        font-weight: bold;
        color: #1d4ed8;
        letter-spacing: -0.5px;
    }
    .brand-tagline {
        font-size: 7pt;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-top: 1px;
    }
    .doc-type {
        font-size: 10pt;
        font-weight: bold;
        color: #0f172a;
    }
    .doc-no {
        font-size: 14pt;
        font-weight: bold;
        color: #1d4ed8;
        font-family: Courier New, monospace;
        margin-top: 3px;
    }
    .doc-date {
        font-size: 7pt;
        color: #94a3b8;
        margin-top: 4px;
    }
    .divider {
        border: none;
        border-top: 1px solid #e2e8f0;
        margin-top: 12px;
    }

    /* ─── INFO CARDS ─── */
    .info-row {
        display: table;
        width: 100%;
        margin-bottom: 18px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .info-card {
        display: table-cell;
        padding: 10px 14px;
        vertical-align: top;
        border-right: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .info-card:last-child { border-right: none; }
    .info-card-label {
        font-size: 6pt;
        font-weight: bold;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }
    .info-card-value {
        font-size: 9pt;
        font-weight: bold;
        color: #0f172a;
    }
    .info-card-sub {
        font-size: 7.5pt;
        color: #64748b;
        margin-top: 2px;
    }

    /* ─── BADGES ─── */
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 7pt;
        font-weight: bold;
        letter-spacing: 0.2px;
    }
    .badge-green  { background: #dcfce7; color: #15803d; }
    .badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .badge-yellow { background: #fef9c3; color: #a16207; }
    .badge-red    { background: #fee2e2; color: #b91c1c; }
    .badge-gray   { background: #f1f5f9; color: #475569; }
    .badge-purple { background: #f3e8ff; color: #7e22ce; }

    /* ─── ALERT BOX ─── */
    .alert {
        border-radius: 5px;
        padding: 8px 12px;
        margin-bottom: 14px;
        font-size: 8.5pt;
        line-height: 1.5;
    }
    .alert-amber {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #78350f;
        border-left: 3px solid #f59e0b;
    }
    .alert-blue {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        border-left: 3px solid #3b82f6;
    }

    /* ─── SECTION HEADER ─── */
    .section-header {
        margin-top: 20px;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 2px solid #e2e8f0;
        display: table;
        width: 100%;
    }
    .section-title {
        display: table-cell;
        font-size: 8pt;
        font-weight: bold;
        color: #1d4ed8;
        text-transform: uppercase;
        letter-spacing: 1px;
        vertical-align: bottom;
    }
    .section-count {
        display: table-cell;
        text-align: right;
        font-size: 7.5pt;
        color: #94a3b8;
        vertical-align: bottom;
    }

    /* ─── LINES TABLE ─── */
    table.lines-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
        font-size: 8pt;
    }
    table.lines-table thead tr {
        background: #1d4ed8;
    }
    table.lines-table thead th {
        padding: 8px 9px;
        color: #ffffff;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        text-align: left;
    }
    table.lines-table thead th.center { text-align: center; }
    table.lines-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
    }
    table.lines-table tbody tr.row-even {
        background: #f8fafc;
    }
    table.lines-table tbody tr.row-odd {
        background: #ffffff;
    }
    table.lines-table tbody td {
        padding: 8px 9px;
        vertical-align: top;
    }
    table.lines-table tbody td.center { text-align: center; }
    .cell-lineno {
        color: #94a3b8;
        font-size: 7.5pt;
    }
    .cell-code {
        font-family: Courier New, monospace;
        font-size: 7.5pt;
        font-weight: bold;
        color: #0f172a;
    }
    .cell-desc {
        font-size: 8pt;
        color: #64748b;
        margin-top: 2px;
    }
    .cell-qty {
        font-weight: bold;
        color: #0f172a;
        font-size: 8.5pt;
    }
    .cell-unit {
        font-size: 7pt;
        color: #64748b;
    }
    .rev-info {
        font-size: 7.5pt;
        color: #374151;
    }
    .rev-info-sub {
        font-size: 7pt;
        color: #94a3b8;
        margin-top: 2px;
    }
    .rev-count-chip {
        display: inline-block;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 6.5pt;
        font-weight: bold;
        padding: 1px 5px;
        border-radius: 10px;
        margin-top: 3px;
    }
    .rejection-note {
        margin-top: 4px;
        padding: 3px 6px;
        background: #fff1f2;
        border-left: 2px solid #f87171;
        font-size: 7pt;
        color: #9f1239;
        border-radius: 0 3px 3px 0;
    }
    .manual-note {
        margin-top: 3px;
        font-size: 7pt;
        color: #065f46;
        background: #f0fdf4;
        padding: 2px 5px;
        border-radius: 3px;
        display: inline-block;
    }
    .pending-hint {
        font-size: 7.5pt;
        color: #94a3b8;
        font-style: italic;
    }

    /* ─── REVISION HISTORY ─── */
    .rev-product-header {
        background: #f1f5f9;
        padding: 7px 10px;
        border-radius: 4px 4px 0 0;
        margin-bottom: 0;
        border: 1px solid #e2e8f0;
        border-bottom: none;
    }
    .rev-product-code {
        font-family: Courier New, monospace;
        font-size: 8pt;
        font-weight: bold;
        color: #0f172a;
    }
    .rev-product-desc {
        font-size: 7.5pt;
        color: #64748b;
        margin-left: 6px;
    }
    table.rev-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        border: 1px solid #e2e8f0;
        font-size: 8pt;
    }
    table.rev-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    table.rev-table thead th {
        padding: 6px 9px;
        font-size: 7pt;
        font-weight: bold;
        color: #64748b;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    table.rev-table tbody td {
        padding: 6px 9px;
        border-bottom: 1px solid #f8fafc;
        vertical-align: top;
    }
    table.rev-table tbody tr:last-child td {
        border-bottom: none;
    }
    table.rev-table tbody tr.active-rev {
        background: #f0fdf4;
    }
    .rev-no {
        font-family: Courier New, monospace;
        font-weight: bold;
        color: #0f172a;
    }
    .rej-text {
        font-size: 7.5pt;
        color: #9f1239;
    }
    .rej-author {
        font-weight: bold;
    }

    /* ─── NOTES ─── */
    .note-card {
        border: 1px solid #fde68a;
        border-left: 3px solid #f59e0b;
        background: #fffbeb;
        border-radius: 0 5px 5px 0;
        padding: 7px 10px;
        margin-bottom: 7px;
    }
    .note-card.note-reply {
        border-color: #d8b4fe;
        border-left-color: #a855f7;
        background: #faf5ff;
        margin-left: 18px;
    }
    .note-card-meta {
        font-size: 7pt;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    .note-card-meta strong {
        color: #374151;
        font-size: 7.5pt;
    }
    .note-card-body {
        font-size: 8.5pt;
        color: #374151;
        line-height: 1.55;
    }
    .note-section-product {
        font-size: 8pt;
        font-weight: bold;
        color: #374151;
        margin: 12px 0 6px 0;
        padding-bottom: 4px;
        border-bottom: 1px dashed #e2e8f0;
    }
    .note-section-product span {
        font-family: Courier New, monospace;
        color: #1d4ed8;
    }

    /* ─── FOOTER ─── */
    .pdf-footer {
        position: fixed;
        bottom: -15mm;
        left: 0;
        right: 0;
        height: 14mm;
        border-top: 1px solid #e2e8f0;
        padding-top: 5px;
        display: table;
        width: 100%;
    }
    .footer-left {
        display: table-cell;
        font-size: 7pt;
        color: #94a3b8;
        vertical-align: middle;
    }
    .footer-right {
        display: table-cell;
        font-size: 7pt;
        color: #94a3b8;
        text-align: right;
        vertical-align: middle;
    }
    .footer-right strong {
        color: #475569;
    }
</style>
</head>
<body>

{{-- FOOTER --}}
<div class="pdf-footer">
    <div class="footer-left">
        <strong style="color:#475569;">{{ $order->order_no }}</strong>
        &nbsp;·&nbsp; {{ $order->supplier->name }}
    </div>
    <div class="footer-right">
        Oluşturulma: {{ now()->format('d.m.Y H:i') }}
        &nbsp;·&nbsp; <strong>Artwork Portal</strong>
    </div>
</div>

{{-- ═══════════════ HEADER ═══════════════ --}}
<div class="header">
    <div class="header-bar"></div>
    <div class="header-table">
        <div class="header-left">
            <div class="brand-name">Artwork Portal</div>
            <div class="brand-tagline">Sipariş Özet Raporu</div>
        </div>
        <div class="header-right">
            <div class="doc-type">Sipariş Detayı</div>
            <div class="doc-no">{{ $order->order_no }}</div>
            <div class="doc-date">Rapor tarihi: {{ now()->format('d.m.Y H:i') }}</div>
        </div>
    </div>
    <hr class="divider">
</div>

{{-- ═══════════════ BİLGİ KARTLARI ═══════════════ --}}
<div class="info-row">
    <div class="info-card" style="width:24%;">
        <div class="info-card-label">Tedarikci</div>
        <div class="info-card-value">{{ $order->supplier->name }}</div>
        <div class="info-card-sub">{{ $order->supplier->code ?: '—' }}</div>
    </div>
    <div class="info-card" style="width:14%;">
        <div class="info-card-label">Siparis Tarihi</div>
        <div class="info-card-value">{{ $order->order_date->format('d.m.Y') }}</div>
    </div>
    <div class="info-card" style="width:14%;">
        <div class="info-card-label">Teslim Tarihi</div>
        <div class="info-card-value">{{ $order->due_date?->format('d.m.Y') ?? '—' }}</div>
    </div>
    <div class="info-card" style="width:24%;">
        <div class="info-card-label">Durum</div>
        @php
            $statusBadge = ['active'=>'badge-green','draft'=>'badge-gray','completed'=>'badge-blue','cancelled'=>'badge-red'][$order->status] ?? 'badge-gray';
            $shipBadge   = ['dispatched'=>'badge-blue','delivered'=>'badge-green','not_found'=>'badge-red'][$order->shipment_status] ?? 'badge-yellow';
        @endphp
        <span class="badge {{ $statusBadge }}">{{ $order->status_label }}</span>
        <div style="margin-top:4px;">
            <span class="badge {{ $shipBadge }}">{{ $order->shipment_status_label }}</span>
        </div>
    </div>
    <div class="info-card" style="width:24%;">
        <div class="info-card-label">Olusturan</div>
        <div class="info-card-value">{{ $order->createdBy?->name ?? '—' }}</div>
        <div class="info-card-sub">{{ $order->created_at->format('d.m.Y') }}</div>
    </div>
</div>

{{-- Mikro / ERP --}}
@if($order->shipment_reference || $order->shipment_synced_at)
<div class="alert alert-blue" style="margin-bottom:14px;">
    <strong>Mikro / ERP:</strong>
    {{ $order->shipment_reference ?: 'Referans bekleniyor' }}
    @if($order->shipment_synced_at)
        &nbsp;&middot;&nbsp; Senkronlanma: {{ $order->shipment_synced_at->format('d.m.Y H:i') }}
    @endif
</div>
@endif

{{-- Siparis notu --}}
@if($order->notes)
<div class="alert alert-amber">
    <strong>Siparis Notu:</strong> {{ $order->notes }}
</div>
@endif

{{-- ═══════════════ SİPARİŞ SATIRLARI ═══════════════ --}}
<div class="section-header">
    <div class="section-title">Siparis Satirlari</div>
    <div class="section-count">{{ $order->lines->count() }} satir</div>
</div>

@php
use App\Enums\ArtworkStatus;
$artworkBadgeMap = [
    ArtworkStatus::APPROVED->value => 'badge-green',
    ArtworkStatus::UPLOADED->value => 'badge-blue',
    ArtworkStatus::REVISION->value => 'badge-yellow',
    ArtworkStatus::PENDING->value  => 'badge-gray',
];
@endphp

<table class="lines-table">
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:20%;">Urun Kodu</th>
            <th style="width:28%;">Aciklama</th>
            <th class="center" style="width:9%;">Miktar</th>
            <th class="center" style="width:13%;">Artwork</th>
            <th style="width:26%;">Revizyon Bilgisi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->lines as $idx => $line)
        @php
            $rowClass    = ($idx % 2 === 0) ? 'row-odd' : 'row-even';
            $activeRev   = $line->artwork?->activeRevision;
            $revCount    = $line->artwork?->revisions->count() ?? 0;
            $statusLabel = $line->artwork_status->label();
            $statusBadge = $artworkBadgeMap[$line->artwork_status->value] ?? 'badge-gray';
        @endphp
        <tr class="{{ $rowClass }}">
            <td class="cell-lineno">{{ $line->line_no }}</td>
            <td>
                <div class="cell-code">{{ $line->product_code }}</div>
            </td>
            <td>
                <div class="cell-desc">{{ $line->description ?: '—' }}</div>
            </td>
            <td class="center">
                <span class="cell-qty">{{ number_format($line->quantity) }}</span>
                @if($line->unit)
                    <div class="cell-unit">{{ $line->unit }}</div>
                @endif
                @if($line->shipped_quantity)
                    <div style="font-size:6.5pt; color:#64748b; margin-top:1px;">Gon: {{ number_format($line->shipped_quantity) }}</div>
                @endif
            </td>
            <td class="center">
                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                @if($line->is_manual_artwork_completed)
                    <div style="margin-top:4px;">
                        <span class="badge badge-purple">Manuel</span>
                    </div>
                @endif
            </td>
            <td>
                @if($activeRev)
                    <div class="rev-info">
                        Rev <strong>#{{ $activeRev->revision_no }}</strong>
                        &nbsp;&middot;&nbsp; {{ $activeRev->uploadedBy?->name ?? '—' }}
                    </div>
                    <div class="rev-info-sub">{{ $activeRev->created_at->format('d.m.Y H:i') }}</div>
                    @if($revCount > 1)
                        <span class="rev-count-chip">{{ $revCount }} revizyon</span>
                    @endif
                    @if($activeRev->latestRejectedApproval ?? null)
                        @php $rej = $activeRev->latestRejectedApproval; @endphp
                        <div class="rejection-note">
                            Revizyon talebi &mdash; {{ $rej->supplier?->name ?? $rej->user?->name ?? '—' }}
                            @if($rej->notes) : {{ $rej->notes }} @endif
                        </div>
                    @endif
                @elseif($line->artwork_status === 'pending')
                    <span class="pending-hint">Artwork bekleniyor</span>
                @else
                    <span style="color:#cbd5e1;">—</span>
                @endif

                @if($line->is_manual_artwork_completed && $line->manualArtworkCompletedBy)
                    <div class="manual-note">
                        Manuel: {{ $line->manualArtworkCompletedBy->name }}
                        @if($line->manual_artwork_completed_at)
                            &mdash; {{ \Carbon\Carbon::parse($line->manual_artwork_completed_at)->format('d.m.Y') }}
                        @endif
                    </div>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═══════════════ REVİZYON GEÇMİŞİ ═══════════════ --}}
@php
    $linesWithRevisions = $order->lines->filter(fn($l) => ($l->artwork?->revisions->count() ?? 0) > 1);
@endphp
@if($linesWithRevisions->isNotEmpty())

<div class="section-header">
    <div class="section-title">Revizyon Gecmisi</div>
    <div class="section-count">{{ $linesWithRevisions->count() }} urun</div>
</div>

@foreach($linesWithRevisions as $line)
<div class="rev-product-header">
    <span class="rev-product-code">{{ $line->product_code }}</span>
    @if($line->description)
        <span class="rev-product-desc">{{ $line->description }}</span>
    @endif
</div>
<table class="rev-table">
    <thead>
        <tr>
            <th style="width:10%;">Rev #</th>
            <th style="width:28%;">Yukleyen</th>
            <th style="width:22%;">Tarih</th>
            <th style="width:14%;">Durum</th>
            <th>Revizyon Notlari</th>
        </tr>
    </thead>
    <tbody>
        @foreach($line->artwork->revisions as $rev)
        @php
            $rejApprovals = $rev->rejectedApprovals ?? collect();
            $rowStyle = $rev->is_active ? 'active-rev' : '';
        @endphp
        <tr class="{{ $rowStyle }}">
            <td>
                <span class="rev-no">#{{ $rev->revision_no }}</span>
                @if($rev->is_active)
                    <span style="color:#16a34a; font-size:8pt;"> &#9679;</span>
                @endif
            </td>
            <td style="font-size:8pt;">{{ $rev->uploadedBy?->name ?? '—' }}</td>
            <td style="font-size:8pt; color:#64748b;">{{ $rev->created_at->format('d.m.Y H:i') }}</td>
            <td>
                @if($rejApprovals->isNotEmpty())
                    <span class="badge badge-yellow">Revizyon</span>
                @elseif($rev->is_active)
                    <span class="badge badge-green">Aktif</span>
                @else
                    <span class="badge badge-gray">Eski</span>
                @endif
            </td>
            <td>
                @foreach($rejApprovals as $rej)
                    <div class="rej-text">
                        <span class="rej-author">{{ $rej->supplier?->name ?? $rej->user?->name ?? '—' }}</span>:
                        {{ $rej->notes ?: '—' }}
                    </div>
                @endforeach
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endforeach
@endif

{{-- ═══════════════ SİPARİŞ NOTLARI ═══════════════ --}}
@if($order->orderNotes->isNotEmpty())

<div class="section-header">
    <div class="section-title">Siparis Notlari</div>
    <div class="section-count">{{ $order->orderNotes->count() }} not</div>
</div>

@foreach($order->orderNotes as $note)
<div class="note-card">
    <div class="note-card-meta">
        <strong>{{ $note->user?->name ?? '—' }}</strong>
        &nbsp;&middot;&nbsp; {{ $note->created_at->format('d.m.Y H:i') }}
    </div>
    <div class="note-card-body">{{ $note->body }}</div>
</div>
@if($note->replies->isNotEmpty())
    @foreach($note->replies as $reply)
    <div class="note-card note-reply">
        <div class="note-card-meta">
            <strong>{{ $reply->user?->name ?? '—' }}</strong>
            &nbsp;&middot;&nbsp; {{ $reply->created_at->format('d.m.Y H:i') }}
            &nbsp;&#8618;&nbsp; Yanit
        </div>
        <div class="note-card-body">{{ $reply->body }}</div>
    </div>
    @endforeach
@endif
@endforeach
@endif

{{-- ═══════════════ SATIR AÇIKLAMALARI ═══════════════ --}}
@php
    $linesWithNotes = $order->lines->filter(fn($l) => $l->lineNotes->isNotEmpty());
@endphp
@if($linesWithNotes->isNotEmpty())

<div class="section-header">
    <div class="section-title">Satir Aciklamalari</div>
    <div class="section-count">{{ $linesWithNotes->count() }} urun</div>
</div>

@foreach($linesWithNotes as $line)
<div class="note-section-product">
    <span>{{ $line->product_code }}</span>
    @if($line->description)
        &nbsp;<span style="font-weight:normal; color:#64748b; font-family: DejaVu Sans, sans-serif;">{{ $line->description }}</span>
    @endif
</div>
@foreach($line->lineNotes as $note)
<div class="note-card">
    <div class="note-card-meta">
        <strong>{{ $note->user?->name ?? '—' }}</strong>
        &nbsp;&middot;&nbsp; {{ $note->created_at->format('d.m.Y H:i') }}
    </div>
    <div class="note-card-body">{{ $note->body }}</div>
</div>
@if($note->replies->isNotEmpty())
    @foreach($note->replies as $reply)
    <div class="note-card note-reply">
        <div class="note-card-meta">
            <strong>{{ $reply->user?->name ?? '—' }}</strong>
            &nbsp;&middot;&nbsp; {{ $reply->created_at->format('d.m.Y H:i') }}
            &nbsp;&#8618;&nbsp; Yanit
        </div>
        <div class="note-card-body">{{ $reply->body }}</div>
    </div>
    @endforeach
@endif
@endforeach
@endforeach
@endif

</body>
</html>
