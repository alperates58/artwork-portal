# Changelog

Tum anlamli surumler bu dosyada tutulur. Surum kaynagi olarak repo icindeki `releases/manifest.json` ile birlikte kullanilir.

## [1.4.1] - 2026-03-25

Ozet:
Mikro Phase 2 cleanup pass ile supplier bazli sync tek aktif yol olarak sabitlendi, siparis kimligi supplier scope'a alindi ve ERP VIEW kontrati daha guvenli hale getirildi.

Temel degisiklikler:
- `purchase_orders` icin kimlik modeli `(supplier_id, order_no)` olacak sekilde guncellendi.
- Zamanlanmis legacy `erp:sync` yolu kaldirildi; scheduler yalniz supplier bazli sync job'ini tetikler.
- Mikro payload normalization tek sinifta toplandi ve beklenen ERP VIEW alias kontrati merkezilestirildi.
- `last_sync_status` degerleri `success`, `failed`, `partial` ile standart hale getirildi.
- `duplicate_order_conflict`, `missing_supplier_mapping`, `invalid_line_identity` ve `endpoint_payload_mismatch` kodlariyla yapilandirilmis conflict loglari eklendi.
- `purchase_orders` seviyesinde hafif kaynak izlenebilirligi icin `erp_source` ve `source_metadata` alanlari eklendi.
- Admin ayarlar ekranindan `use_direct_db` yuzeyi kaldirildi; serbest SQL / dogrudan DB sorgusu eklenmedi.

Sema degisiklikleri:
- `purchase_orders` composite unique: `supplier_id + order_no`
- `purchase_orders.erp_source`
- `purchase_orders.source_metadata`

Operasyon notlari:
- Scheduler cron araligi `MIKRO_SYNC_INTERVAL` uzerinden hesaplanir ve tek aktif yol `SyncAllActiveSuppliersJob` olarak kalir.
- Queue worker calismiyorsa manuel sync butonlari yalniz kuyruga is birakir.
- ERP endpoint shape production ortaminda gercek servis ile hala manuel dogrulanmalidir.
- SQL VIEW tarafinda tercih edilen alias kontrati: `order_no`, `line_no`, `stock_code`, `stock_name`, `order_qty`, `supplier_code`, `supplier_name`, `order_date`.

## [1.4.0] - 2026-03-25

Ozet:
Mikro Phase 2 ile supplier bazli siparis senkronizasyonu, queue/scheduler akisi, admin supplier sync kontrolleri ve shipment gorunurlugu mevcut siparis yapisi korunarak eklendi.

Temel degisiklikler:
- `supplier_mikro_accounts` uzerinden supplier bazli Mikro siparis sync akisi eklendi.
- Yeni `SyncSupplierOrdersJob` ve `SyncAllActiveSuppliersJob` ile kuyruk temelli senkron mimarisi kuruldu.
- Admin supplier detay ekranina manuel sync butonu, son sync durumu ve hata ozeti gorunurlugu eklendi.
- `purchase_order_lines` seviyesinde `shipped_quantity` destegi eklenerek portal ve admin siparis detaylarinda read-only shipment bilgisi gosterilmeye baslandi.
- Supplier izolasyonu ve Mikro hata senaryolari icin yeni feature testleri eklendi.

Sema degisiklikleri:
- `supplier_mikro_accounts.last_sync_status`
- `supplier_mikro_accounts.last_sync_error`
- `purchase_order_lines.shipped_quantity`

Operasyon notlari:
- Scheduler artik `SyncAllActiveSuppliersJob` isini her 5 dakikada bir tetikler.
- Queue worker calismiyorsa manuel sync butonlari yalniz kuyruga is birakir, senkron tamamlanmaz.
- Mikro endpoint belirsizligi tek servis metodunda izole edilmistir; production dogrulamasi yine gercek endpoint ile yapilmalidir.

## [1.3.0] - 2026-03-25

Ozet:
Regression fix pass ile Docker icindeki gercek asset build akisi duzeltildi, test regresyonlari giderildi ve mevcut update sistemi korunarak surum disiplini ileri tasindi.

Temel degisiklikler:
- Asset build icin `app` container yerine ayri `node` service kullanilacak Docker workflow tanimlandi.
- `docker compose run --rm node npm ci` ve `docker compose run --rm node npm run build` akisi dogrulandi.
- `SpacesStorageService` path sanitization davranisi guvenli beklenen formata geri alindi.
- `SpacesStorageServiceTest`, `MikroIntegrationTest` ve `SetupWizardTest` tekrar gecer hale getirildi.
- Kurulum scriptleri ve `KURULUM.md` yeni Docker asset hatti ile uyumlu hale getirildi.

Sema degisiklikleri:
- Bu surumde yeni migration yok.

Operasyon notlari:
- Asset build artik Docker icindeki `node` service uzerinden calistirilmalidir.
- Production deploy sonrasi `docker compose run --rm node npm ci` ve `docker compose run --rm node npm run build` adimlari atlanmamalidir.
- Full test suite bu surum icin tekrar yesile dondurulmustur.

## [1.2.0] - 2026-03-25

Ozet:
Update sistemi release manifest, schema farki gorunurlugu, guvenli prepare/onay akisi ve daha guclu history yapisi ile Phase 2 seviyesine tasindi. Frontend asset hatti production dostu yerel build yapisina hazirlandi. DigitalOcean/Redis/kurulum dokumantasyonu netlestirildi.

Temel degisiklikler:
- Admin ayarlar ekranina hedef surum, release notlari, migration/schema bilgisi ve uyarilar eklendi.
- `portal_update_events` kayitlari surum gecisi, release ozeti, change listesi ve uygulanan migration bilgisi ile zenginlestirildi.
- Web istegi icinden tehlikeli deploy calistirmadan "update hazirla" akisi eklendi.
- `Vite + Tailwind` tabanli yerel asset hatti tanimlandi; build varsa CDN yerine repo ici asset kullanimi desteklendi.
- KURULUM ve README icerigi DigitalOcean, Redis, Spaces, timezone ve surum disiplini acisindan sertlestirildi.

Sema degisiklikleri:
- `portal_update_events` tablosuna release/history gorunurlugu icin ek kolonlar eklendi.

Operasyon notlari:
- Production deploy sirasinda `npm install` ve `npm run build` calistirilmalidir.
- Update oncesi DB ve depolama yedegi alinmalidir.
- Redis, queue ve scheduler servisleri update sonrasi dogrulanmalidir.

## [1.1.0] - 2026-03-24

Ozet:
Admin update gorunurlugu icin temel altyapi eklendi.

Temel degisiklikler:
- `portal:update` ve `portal:update:check` komutlari eklendi.
- `portal_update_events` tablosu olusturuldu.
- Admin ayarlar ekranina commit, branch ve update history gorunurlugu eklendi.
