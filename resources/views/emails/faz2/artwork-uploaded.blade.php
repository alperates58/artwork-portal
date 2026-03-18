<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Artwork Yüklendi</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <div style="background: #2563eb; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="color: #fff; margin: 0; font-size: 20px;">Yeni Artwork Hazır</h1>
    </div>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-top: none; padding: 24px; border-radius: 0 0 8px 8px;">

        <p>Merhaba,</p>

        <p>
            <strong>{{ $order->order_no }}</strong> numaralı siparişinize ait
            <strong>{{ $line->product_code }}</strong> ürününün artwork dosyası güncellendi.
        </p>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin: 16px 0;">
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">Sipariş No</td>
                    <td style="font-weight: bold; padding: 4px 0;">{{ $order->order_no }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">Ürün</td>
                    <td style="padding: 4px 0;">{{ $line->product_code }} — {{ $line->description }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">Revizyon</td>
                    <td style="padding: 4px 0;">Rev.{{ $revision->revision_no }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">Dosya</td>
                    <td style="padding: 4px 0;">{{ $revision->original_filename }}</td>
                </tr>
            </table>
        </div>

        <div style="text-align: center; margin: 24px 0;">
            <a href="{{ $downloadUrl }}"
               style="background: #2563eb; color: #fff; padding: 12px 28px; border-radius: 6px;
                      text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block;">
                Dosyayı İndir
            </a>
        </div>

        <p style="font-size: 12px; color: #94a3b8;">
            Bu link 15 dakika geçerlidir. Portal üzerinden de erişebilirsiniz:
            <a href="{{ config('app.url') }}" style="color: #2563eb;">{{ config('app.url') }}</a>
        </p>

    </div>

</body>
</html>
