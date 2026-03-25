# Changelog

Tüm anlamlı sürümler bu dosyada tutulur. Sürüm kaynağı olarak repo içindeki `releases/manifest.json` ile birlikte kullanılır.

## [1.2.0] - 2026-03-25

Özet:
Update sistemi release manifest, schema farkı görünürlüğü, güvenli prepare/onay akışı ve daha güçlü history yapısı ile Phase 2 seviyesine taşındı. Frontend asset hattı production dostu yerel build yapısına hazırlandı. DigitalOcean/Redis/kurulum dokümantasyonu netleştirildi.

Temel değişiklikler:
- Admin ayarlar ekranına hedef sürüm, release notları, migration/schema bilgisi ve uyarılar eklendi.
- `portal_update_events` kayıtları sürüm geçişi, release özeti, change listesi ve uygulanan migration bilgisi ile zenginleştirildi.
- Web isteği içinden tehlikeli deploy çalıştırmadan "update hazırla" akışı eklendi.
- `Vite + Tailwind` tabanlı yerel asset hattı tanımlandı; build varsa CDN yerine repo içi asset kullanımı desteklendi.
- KURULUM ve README içeriği DigitalOcean, Redis, Spaces, timezone ve sürüm disiplini açısından sertleştirildi.

Şema değişiklikleri:
- `portal_update_events` tablosuna release/history görünürlüğü için ek kolonlar eklendi.

Operasyon notları:
- Production deploy sırasında `npm install` ve `npm run build` çalıştırılmalıdır.
- Update öncesi DB ve depolama yedeği alınmalıdır.
- Redis, queue ve scheduler servisleri update sonrası doğrulanmalıdır.

## [1.1.0] - 2026-03-24

Özet:
Admin update görünürlüğü için temel altyapı eklendi.

Temel değişiklikler:
- `portal:update` ve `portal:update:check` komutları eklendi.
- `portal_update_events` tablosu oluşturuldu.
- Admin ayarlar ekranına commit, branch ve update history görünürlüğü eklendi.
