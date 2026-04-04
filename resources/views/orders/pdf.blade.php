<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Sipariş {{ $order->order_no }} - {{ config('portal.brand_name') }}</title>
<style>
@page { margin: 14mm 16mm 19mm 16mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; line-height: 1.5; color: #334155; background: #fff; }
table { width: 100%; border-collapse: collapse; }
.page-shell { padding: 0 1mm; }
.r { text-align: right; } .m { color: #94a3b8; } .pre { white-space: pre-line; } .keep { page-break-inside: avoid; }
.badge { display: inline-block; padding: 3px 9px; border-radius: 999px; border: 1px solid transparent; font-size: 7pt; font-weight: bold; line-height: 1.2; white-space: nowrap; }
.b-ok { color: #15803d; background: #ecfdf5; border-color: #bbf7d0; } .b-warn { color: #a16207; background: #fefce8; border-color: #fde68a; }
.b-bad { color: #b91c1c; background: #fef2f2; border-color: #fecaca; } .b-info { color: #c2410c; background: #fff7ed; border-color: #fed7aa; }
.b-muted { color: #475569; background: #f8fafc; border-color: #e2e8f0; } .b-brand { color: #6d28d9; background: #f5f3ff; border-color: #ddd6fe; }
.foot { position: fixed; left: 0; right: 0; bottom: -14mm; height: 11mm; padding-top: 3mm; border-top: 1px solid #e2e8f0; }
.foot td { width: 50%; font-size: 7.2pt; color: #94a3b8; vertical-align: middle; } .foot strong { color: #475569; }
.hero { margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; } .hero-top { height: 5px; background: #7c3aed; }
.hero-main { padding: 15px 16px; vertical-align: top; } .hero-side { width: 34%; padding: 15px 16px; vertical-align: top; text-align: right; background: #faf7ff; border-left: 1px solid #ede9fe; }
.eyebrow { font-size: 6.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1.1px; color: #8b5cf6; }
.hero-title { margin-top: 6px; font-size: 20pt; font-weight: bold; line-height: 1.1; letter-spacing: -.5px; color: #0f172a; }
.hero-sub { margin-top: 5px; font-size: 8.2pt; color: #64748b; } .order-no { margin-top: 8px; font-family: Courier New, monospace; font-size: 16pt; font-weight: bold; color: #6d28d9; }
.hero-meta { margin-top: 6px; font-size: 7.5pt; color: #64748b; } .hero-badges { margin-top: 12px; } .hero-badges .badge { margin-left: 6px; }
.sum { margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
.sum td { padding: 12px 14px; vertical-align: top; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; } .sum td:last-child { border-right: none; } .sum tr:last-child td { border-bottom: none; }
.label { font-size: 6.6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; } .metric .label { color: #7c3aed; }
.val { margin-top: 6px; font-size: 10pt; font-weight: bold; line-height: 1.35; color: #0f172a; } .sub { margin-top: 3px; font-size: 8pt; line-height: 1.45; color: #64748b; }
.metric td { background: #f8fafc; } .n { margin-top: 6px; font-size: 16pt; font-weight: bold; line-height: 1; } .n-brand { color: #6d28d9; } .n-warn { color: #b45309; } .n-bad { color: #b91c1c; } .n-info { color: #c2410c; }
.ctx { margin-bottom: 8px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 14px; } .ctx-brand { background: #faf7ff; border-color: #e9d5ff; } .ctx-warn { background: #fffdf5; border-color: #fde68a; }
.ctx .label { color: #8b5cf6; } .ctx-warn .label { color: #b45309; } .ctx .val { font-size: 9pt; color: #1e293b; }
.sec { margin-top: 16px; } .sec-head { margin-bottom: 8px; } .sec-title-cell { vertical-align: bottom; } .sec-count-cell { width: 1%; white-space: nowrap; text-align: right; vertical-align: bottom; }
.sec-kicker { font-size: 6.7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #a78bfa; } .sec-title { margin-top: 3px; font-size: 11pt; font-weight: bold; color: #0f172a; } .sec-copy { margin-top: 2px; font-size: 8pt; color: #64748b; }
.pill { display: inline-block; padding: 4px 10px; border-radius: 999px; border: 1px solid #ddd6fe; background: #f5f3ff; color: #6d28d9; font-size: 7pt; font-weight: bold; }
.card { border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; background: #fff; }
.line-table { table-layout: fixed; } .line-table th { padding: 9px 12px; border-bottom: 1px solid #ede9fe; background: #faf7ff; color: #6d28d9; font-size: 6.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; text-align: left; }
.line-table td { padding: 10px 12px; vertical-align: top; border-bottom: 1px solid #f1f5f9; } .line-table tr:last-child td { border-bottom: none; }
.idx { display: inline-block; min-width: 24px; padding: 3px 7px; border-radius: 999px; border: 1px solid #ddd6fe; background: #f5f3ff; color: #6d28d9; font-size: 7pt; font-weight: bold; text-align: center; }
.code { font-family: Courier New, monospace; font-size: 8pt; font-weight: bold; line-height: 1.35; color: #0f172a; } .desc { margin-top: 4px; font-size: 8pt; line-height: 1.55; color: #64748b; }
.qty { font-size: 9pt; font-weight: bold; line-height: 1.2; color: #0f172a; } .qty-sub { margin-top: 3px; font-size: 7pt; line-height: 1.4; color: #94a3b8; }
.stack > div + div { margin-top: 6px; } .rev-title { font-size: 8pt; font-weight: bold; line-height: 1.35; color: #1e293b; } .rev-meta { margin-top: 3px; font-size: 7.5pt; line-height: 1.45; color: #64748b; }
.mini { margin-top: 7px; padding: 6px 8px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 7.3pt; line-height: 1.5; } .mini-brand { color: #6d28d9; background: #faf7ff; border-color: #e9d5ff; } .mini-bad { color: #b91c1c; background: #fef2f2; border-color: #fecaca; } .mini-ok { color: #047857; background: #ecfdf5; border-color: #bbf7d0; } .empty { font-size: 8pt; color: #94a3b8; font-style: italic; }
.group { margin-bottom: 12px; page-break-inside: avoid; } .group-head { padding: 12px 14px; background: #faf7ff; border-bottom: 1px solid #ede9fe; }
.group-title { vertical-align: top; } .group-count { width: 1%; white-space: nowrap; text-align: right; vertical-align: top; } .group .code { color: #6d28d9; }
.rev-table th { padding: 9px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 6.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; text-align: left; }
.rev-table td { padding: 9px 12px; border-bottom: 1px solid #f8fafc; vertical-align: top; font-size: 8pt; line-height: 1.45; } .rev-table tr:last-child td { border-bottom: none; } .rev-table .active td { background: #f0fdf4; }
.rev-no { font-family: Courier New, monospace; font-weight: bold; color: #0f172a; } .rev-dot { color: #16a34a; font-size: 8pt; } .rev-note { margin-top: 4px; font-size: 7.5pt; color: #9f1239; }
.notes { padding: 14px; } .notes-group { padding: 14px; page-break-inside: avoid; } .notes-group + .notes-group { border-top: 1px solid #f1f5f9; } .notes-head { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0; }
.note { margin-bottom: 10px; padding: 10px 12px; border: 1px solid #f1f5f9; border-left: 4px solid #f59e0b; border-radius: 12px; background: #fffdf7; page-break-inside: avoid; } .note.reply { margin-left: 18px; border-left-color: #8b5cf6; background: #faf7ff; } .note:last-child { margin-bottom: 0; }
.note-meta { font-size: 7pt; line-height: 1.4; color: #94a3b8; } .note-author { font-size: 8pt; font-weight: bold; color: #334155; } .note-body { margin-top: 5px; font-size: 8.4pt; line-height: 1.65; color: #334155; }
</style>
</head>
<body>
@php
    use App\Enums\ArtworkStatus;
    $generatedAt = now();
    $brandName = config('portal.brand_name');
    $brandTagline = config('portal.brand_tagline');
    $lines = $order->lines;
    $linesWithRevisions = $lines->filter(fn ($line) => ($line->artwork?->revisions->count() ?? 0) > 1);
    $linesWithNotes = $lines->filter(fn ($line) => $line->lineNotes->isNotEmpty());
    $pendingArtworkCount = $lines->filter(fn ($line) => (($line->artwork_status?->value ?? $line->artwork_status ?? ArtworkStatus::PENDING->value) === ArtworkStatus::PENDING->value))->count();
    $revisionArtworkCount = $lines->filter(fn ($line) => (($line->artwork_status?->value ?? $line->artwork_status) === ArtworkStatus::REVISION->value))->count();
    $manualArtworkCount = $lines->filter(fn ($line) => $line->is_manual_artwork_completed)->count();
    $orderStatusBadge = match ($order->status) { 'active' => 'b-ok', 'draft' => 'b-muted', 'completed' => 'b-brand', 'cancelled' => 'b-bad', default => 'b-muted' };
    $shipmentBadge = match ($order->shipment_status) { 'dispatched' => 'b-info', 'delivered' => 'b-ok', 'not_found' => 'b-bad', default => 'b-warn' };
    $artworkBadgeMap = [ArtworkStatus::APPROVED->value => 'b-ok', ArtworkStatus::UPLOADED->value => 'b-info', ArtworkStatus::REVISION->value => 'b-bad', ArtworkStatus::PENDING->value => 'b-warn'];
@endphp

<div class="foot">
    <table><tr>
        <td><strong>{{ $brandName }}</strong> &nbsp;&middot;&nbsp; {{ $order->order_no }}</td>
        <td class="r">{{ $order->supplier->name }} &nbsp;&middot;&nbsp; {{ $generatedAt->format('d.m.Y H:i') }}</td>
    </tr></table>
</div>

<div class="page-shell">
<div class="hero keep">
    <div class="hero-top"></div>
    <table><tr>
        <td class="hero-main">
            <div class="eyebrow">{{ $brandName }}</div>
            <div class="hero-title">Sipariş Özeti</div>
            <div class="hero-sub">{{ $brandTagline }}</div>
        </td>
        <td class="hero-side">
            <div class="eyebrow r">Belge numarası</div>
            <div class="order-no">{{ $order->order_no }}</div>
            <div class="hero-meta">Rapor tarihi {{ $generatedAt->format('d.m.Y H:i') }}</div>
            <div class="hero-badges">
                <span class="badge {{ $orderStatusBadge }}">{{ $order->status_label }}</span>
                <span class="badge {{ $shipmentBadge }}">{{ $order->shipment_status_label }}</span>
            </div>
        </td>
    </tr></table>
</div>

<div class="sum keep">
    <table>
        <tr>
            <td style="width:40%;">
                <div class="label">Tedarikçi</div>
                <div class="val">{{ $order->supplier->name }}</div>
                <div class="sub">{{ $order->supplier->code ?: 'Kod bilgisi bulunmuyor' }}</div>
            </td>
            <td style="width:15%;">
                <div class="label">Sipariş Tarihi</div>
                <div class="val">{{ $order->order_date->format('d.m.Y') }}</div>
            </td>
            <td style="width:15%;">
                <div class="label">Teslim Tarihi</div>
                <div class="val">{{ $order->due_date?->format('d.m.Y') ?? '-' }}</div>
            </td>
            <td style="width:30%;">
                <div class="label">Oluşturan</div>
                <div class="val">{{ $order->createdBy?->name ?? '-' }}</div>
                <div class="sub">Kayıt {{ $order->created_at->format('d.m.Y H:i') }}</div>
            </td>
        </tr>
        <tr class="metric">
            <td style="width:25%;"><div class="label">Toplam Satır</div><div class="n n-brand">{{ $lines->count() }}</div><div class="sub">Siparişteki ürün satırı</div></td>
            <td style="width:25%;"><div class="label">Bekleyen Artwork</div><div class="n n-warn">{{ $pendingArtworkCount }}</div><div class="sub">Henüz tamamlanmayan satır</div></td>
            <td style="width:25%;"><div class="label">Revizyon Gerekli</div><div class="n n-bad">{{ $revisionArtworkCount }}</div><div class="sub">Düzeltme talebi bekleyen satır</div></td>
            <td style="width:25%;"><div class="label">Manuel İşaretli</div><div class="n n-info">{{ $manualArtworkCount }}</div><div class="sub">Sistem dışında tamamlanan satır</div></td>
        </tr>
    </table>
</div>

@if($order->shipment_reference || $order->shipment_synced_at)
    <div class="ctx ctx-brand">
        <div class="label">Mikro / ERP Bilgisi</div>
        <div class="val">{{ $order->shipment_reference ?: 'Referans bekleniyor' }}</div>
        <div class="sub">
            @if($order->shipment_synced_at)
                Son senkron {{ $order->shipment_synced_at->format('d.m.Y H:i') }}
            @else
                Henüz senkron bilgisi bulunmuyor
            @endif
        </div>
    </div>
@endif

@if($order->notes)
    <div class="ctx ctx-warn">
        <div class="label">Sipariş Notu</div>
        <div class="val pre">{{ $order->notes }}</div>
    </div>
@endif

<div class="sec">
    <table class="sec-head"><tr>
        <td class="sec-title-cell">
            <div class="sec-kicker">Sipariş Akışı</div>
            <div class="sec-title">Sipariş Satırları</div>
            <div class="sec-copy">Her ürün için miktar, artwork durumu ve güncel revizyon özeti.</div>
        </td>
        <td class="sec-count-cell"><span class="pill">{{ $lines->count() }} satır</span></td>
    </tr></table>
    <div class="card">
        <table class="line-table">
            <thead><tr>
                <th style="width:9%;">Satır</th>
                <th style="width:34%;">Ürün</th>
                <th style="width:12%;">Miktar</th>
                <th style="width:18%;">Artwork Durumu</th>
                <th style="width:27%;">Aktif Revizyon / Notlar</th>
            </tr></thead>
            <tbody>
                @foreach($lines as $line)
                    @php
                        $artworkValue = $line->artwork_status?->value ?? $line->artwork_status ?? ArtworkStatus::PENDING->value;
                        $statusLabel = $line->artwork_status?->label() ?? 'Bekliyor';
                        $statusBadge = $artworkBadgeMap[$artworkValue] ?? 'b-muted';
                        $activeRevision = $line->artwork?->activeRevision;
                        $revisionCount = $line->artwork?->revisions->count() ?? 0;
                        $latestRejection = $activeRevision?->latestRejectedApproval;
                    @endphp
                    <tr>
                        <td><span class="idx">{{ $line->line_no }}</span></td>
                        <td><div class="code">{{ $line->product_code }}</div><div class="desc">{{ $line->description ?: 'Açıklama bulunmuyor.' }}</div></td>
                        <td>
                            <div class="qty">{{ number_format($line->quantity) }}</div>
                            <div class="qty-sub">{{ $line->unit ?: 'Adet' }}</div>
                            @if(! is_null($line->shipped_quantity))
                                <div class="qty-sub">Sevk edilen: {{ number_format($line->shipped_quantity) }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="stack">
                                <div><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></div>
                                @if($line->is_manual_artwork_completed)
                                    <div><span class="badge b-brand">Manuel gönderim</span></div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($activeRevision)
                                <div class="rev-title">Revizyon #{{ $activeRevision->revision_no }}</div>
                                <div class="rev-meta">{{ $activeRevision->uploadedBy?->name ?? '-' }} &nbsp;&middot;&nbsp; {{ $activeRevision->created_at->format('d.m.Y H:i') }}</div>
                                @if($revisionCount > 1)
                                    <div class="mini mini-brand">{{ $revisionCount }} revizyon kaydı bulunuyor.</div>
                                @endif
                                @if($latestRejection)
                                    <div class="mini mini-bad">
                                        Revizyon talebi: {{ $latestRejection->supplier?->name ?? $latestRejection->user?->name ?? '-' }}
                                        @if($latestRejection->notes)
                                            <span class="pre">&nbsp;&middot;&nbsp; {{ $latestRejection->notes }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if($line->is_manual_artwork_completed && $line->manualArtworkCompletedBy)
                                    <div class="mini mini-ok">
                                        Manuel işaretleyen: {{ $line->manualArtworkCompletedBy->name }}
                                        @if($line->manual_artwork_completed_at)
                                            &nbsp;&middot;&nbsp; {{ $line->manual_artwork_completed_at->format('d.m.Y H:i') }}
                                        @endif
                                    </div>
                                @endif
                            @elseif($line->is_manual_artwork_completed)
                                <div class="rev-title">Manuel olarak tamamlandı</div>
                                <div class="rev-meta">
                                    {{ $line->manualArtworkCompletedBy?->name ?? '-' }}
                                    @if($line->manual_artwork_completed_at)
                                        &nbsp;&middot;&nbsp; {{ $line->manual_artwork_completed_at->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                                @if($line->manual_artwork_note)
                                    <div class="mini mini-ok pre">{{ $line->manual_artwork_note }}</div>
                                @endif
                            @else
                                <div class="empty">Henüz aktif revizyon bulunmuyor.</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($linesWithRevisions->isNotEmpty())
    <div class="sec">
        <table class="sec-head"><tr>
            <td class="sec-title-cell">
                <div class="sec-kicker">Geçmiş Kayıtlar</div>
                <div class="sec-title">Revizyon Geçmişi</div>
                <div class="sec-copy">Birden fazla revizyon içeren satırlar için hareket özeti.</div>
            </td>
            <td class="sec-count-cell"><span class="pill">{{ $linesWithRevisions->count() }} ürün</span></td>
        </tr></table>

        @foreach($linesWithRevisions as $line)
            <div class="card group">
                <div class="group-head">
                    <table><tr>
                        <td class="group-title">
                            <div class="code">{{ $line->product_code }}</div>
                            @if($line->description)
                                <div class="desc">{{ $line->description }}</div>
                            @endif
                        </td>
                        <td class="group-count"><span class="pill">{{ $line->artwork->revisions->count() }} revizyon</span></td>
                    </tr></table>
                </div>
                <table class="rev-table">
                    <thead><tr>
                        <th style="width:12%;">Revizyon</th>
                        <th style="width:26%;">Yükleyen</th>
                        <th style="width:21%;">Tarih</th>
                        <th style="width:16%;">Durum</th>
                        <th style="width:25%;">Revizyon Notları</th>
                    </tr></thead>
                    <tbody>
                        @foreach($line->artwork->revisions as $revision)
                            @php $rejections = $revision->rejectedApprovals ?? collect(); @endphp
                            <tr class="{{ $revision->is_active ? 'active' : '' }}">
                                <td>
                                    <span class="rev-no">#{{ $revision->revision_no }}</span>
                                    @if($revision->is_active)
                                        <span class="rev-dot">&nbsp;&#9679;</span>
                                    @endif
                                </td>
                                <td>{{ $revision->uploadedBy?->name ?? '-' }}</td>
                                <td class="m">{{ $revision->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($rejections->isNotEmpty())
                                        <span class="badge b-bad">Revizyon</span>
                                    @elseif($revision->is_active)
                                        <span class="badge b-ok">Aktif</span>
                                    @else
                                        <span class="badge b-muted">Arşiv</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($rejections as $rejection)
                                        <div class="rev-note pre">
                                            {{ $rejection->supplier?->name ?? $rejection->user?->name ?? '-' }}
                                            @if($rejection->notes)
                                                &nbsp;&middot;&nbsp; {{ $rejection->notes }}
                                            @endif
                                        </div>
                                    @empty
                                        <span class="m">Not bulunmuyor</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endif

@if($order->orderNotes->isNotEmpty())
    <div class="sec">
        <table class="sec-head"><tr>
            <td class="sec-title-cell">
                <div class="sec-kicker">İletişim</div>
                <div class="sec-title">Sipariş Notları</div>
                <div class="sec-copy">Sipariş seviyesinde bırakılan not ve yanıt geçmişi.</div>
            </td>
            <td class="sec-count-cell"><span class="pill">{{ $order->orderNotes->count() }} not</span></td>
        </tr></table>

        <div class="card notes">
            @foreach($order->orderNotes as $note)
                <div class="note">
                    <div class="note-meta"><span class="note-author">{{ $note->user?->name ?? '-' }}</span> &nbsp;&middot;&nbsp; {{ $note->created_at->format('d.m.Y H:i') }}</div>
                    <div class="note-body pre">{{ $note->body }}</div>
                </div>
                @foreach($note->replies as $reply)
                    <div class="note reply">
                        <div class="note-meta"><span class="note-author">{{ $reply->user?->name ?? '-' }}</span> &nbsp;&middot;&nbsp; {{ $reply->created_at->format('d.m.Y H:i') }} &nbsp;&middot;&nbsp; Yanıt</div>
                        <div class="note-body pre">{{ $reply->body }}</div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
@endif

@if($linesWithNotes->isNotEmpty())
    <div class="sec">
        <table class="sec-head"><tr>
            <td class="sec-title-cell">
                <div class="sec-kicker">Satır İletişimi</div>
                <div class="sec-title">Satır Açıklamaları</div>
                <div class="sec-copy">Ürün bazında yazılan açıklama ve yanıtlar.</div>
            </td>
            <td class="sec-count-cell"><span class="pill">{{ $linesWithNotes->count() }} ürün</span></td>
        </tr></table>

        <div class="card">
            @foreach($linesWithNotes as $line)
                <div class="notes-group">
                    <div class="notes-head">
                        <div class="code">{{ $line->product_code }}</div>
                        @if($line->description)
                            <div class="desc">{{ $line->description }}</div>
                        @endif
                    </div>

                    @foreach($line->lineNotes as $note)
                        <div class="note">
                            <div class="note-meta"><span class="note-author">{{ $note->user?->name ?? '-' }}</span> &nbsp;&middot;&nbsp; {{ $note->created_at->format('d.m.Y H:i') }}</div>
                            <div class="note-body pre">{{ $note->body }}</div>
                        </div>
                        @foreach($note->replies as $reply)
                            <div class="note reply">
                                <div class="note-meta"><span class="note-author">{{ $reply->user?->name ?? '-' }}</span> &nbsp;&middot;&nbsp; {{ $reply->created_at->format('d.m.Y H:i') }} &nbsp;&middot;&nbsp; Yanıt</div>
                                <div class="note-body pre">{{ $reply->body }}</div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif

</div>
</body>
</html>
