# Changelog

Tum anlamli surumler bu dosyada tutulur. Surum kaynagi olarak repo icindeki `releases/manifest.json` ile birlikte kullanilir.

## [1.9.1] - 2026-03-27

Ozet:
Production parity pass ile supplier mapping drift'i guvenli sekilde onarildi ve dashboard bekleyen satir metrik cache'i status gecislerinde merkezi olarak invalid edilir hale getirildi.

Temel degisiklikler:
- Supplier portal erisimi mapping-tablosu merkezli korunarak, supplier role kullanicilarda eksik `supplier_users` kaydi runtime'da onarilir ve yeni kayit drift'i model seviyesinde engellenir hale getirildi.
- Eksik supplier mapping kayitlarini idempotent olarak tamamlayan yeni repair migration eklendi; mevcut historical backfill migration'ina ek olarak parity onarimi guclendirildi.
- Dashboard cache anahtarlari merkezi serviste toplandi; artwork upload, gallery reuse, approve, reject ve dashboard metriklerini etkileyen siparis degisikliklerinde ilgili cache dogrudan temizlenir hale geldi.
- Yeni regression testleri ile eksik mappingten supplier gorunurlugu toparlanmasi ve cache isitilmis dashboard metriginde upload/approval/reject sonrasi tazelenme davranisi dogrulandi.

Sema degisiklikleri:
- `2026_03_27_201500_repair_missing_supplier_user_mappings`

Operasyon notlari:
- Production ortaminda yeni migration uygulanmissa, eski supplier kullanicilardaki eksik `supplier_users` satirlari otomatik olarak tamamlanir.
- Test/local ortaminda `array` cache ve `sync` queue kullanildigi icin stale dashboard davranisi maskelenebilir; production parity dogrulamasinda Redis cache anahtarlarinin temizlendigini kontrol edin.
- Queue worker yalniz bildirim yan etkileri icin gereklidir; dashboard metrik dogrulugu artik queue tamamlanmasina bagli degildir.

## [1.9.0] - 2026-03-26

Ozet:
Artwork galerisi daha kullanilabilir bir grid deneyimine tasindi; UTF-8 sorunlari, dosya tipi farkindaligi, preview akisi ve reuse ekraninin filtrelenebilirligi guclendirildi.

Temel degisiklikler:
- Admin `Artwork Galerisi` ekrani kart/grid duzenine alindi; thumbnail veya dosya ikonu, kategori, etiketler, dosya tipi, boyut, kullanim sayisi ve son kullanim bilgisi eklendi.
- Admin galeri ve artwork upload/reuse ekranlarina ortak `Goruntule` preview modal akisi eklendi; gorseller hafif preview ile, diger dosyalar metadata odakli sunulur.
- Galeri, kategori, etiket, buton ve placeholder metinlerinde UTF-8/Turkce duzeltmeleri yapildi; bozuk dosya adlari presentation seviyesinde normalize edilir hale getirildi.
- Upload ekranindaki galeri secim paneli kart tabanli hale getirildi; kategori ve etiket filtreleri ayni backend sorgusu ile reuse akisina daha gorunur sekilde baglandi.
- Artwork gallery presentation helper'lari, image preview endpoint'i ve yeni feature/unit testleri ile dosya tipi farkindaligi ve usage gorunurlugu sertlestirildi.

Sema degisiklikleri:
- Bu surumde yeni migration yok.

Operasyon notlari:
- Gorsel preview route'u yalnizca admin/graphic upload yetkisi olan ic kullanicilar icin aciktir.
- Spaces kullanilan ortamlarda image preview gecici inline URL ile sunulur; local diskte mevcut private storage akisi korunur.
- Frontend degisikliklerinin production ortaminda gorunmesi icin build alinmis assetler deploy edilmelidir.

## [1.8.0] - 2026-03-26

Ozet:
Merkezi artwork galerisi, yeniden kullanim akisi ve kullanim izlenebilirligi mevcut revision sistemi korunarak eklendi.

Temel degisiklikler:
- `artwork_gallery`, `artwork_categories`, `artwork_tags` ve `artwork_gallery_usages` ile tekrar kullanilabilir artwork ana deposu eklendi.
- Artwork upload ekrani `Yeni dosya yukle` ve `Galeriden sec` olmak uzere iki kaynak tipini destekler hale getirildi.
- Yeni dosya yuklemeleri artik otomatik olarak galeriye kaydolur; galeriden secimde fiziksel dosya tekrar yuklenmeden yeni revision olusur.
- Admin paneline arama, kategori, etiket, kullanim gecmisi ve temel duzenleme aksiyonlariyla `Artwork Galerisi` bolumu eklendi.
- Usage kayitlari supplier, order ve order line baglami ile tutulmaya baslandi; upload ve reuse akislarina ait test kapsami genisletildi.

Sema degisiklikleri:
- `artwork_categories`
- `artwork_tags`
- `artwork_gallery`
- `artwork_gallery_tag`
- `artwork_gallery_usages`
- `artwork_revisions.artwork_gallery_id`

Operasyon notlari:
- Reuse akisi yeni fiziksel dosya olusturmaz; mevcut galeri path bilgisini revision kaydina referans olarak baglar.
- Supplier portali galeriye dogrudan erismez; mevcut aktif revision ve download authorization kurallari korunur.
- Yeni admin ekranlarinin guncel assetlerle gorunmesi icin frontend build alinmalidir.

## [1.7.0] - 2026-03-26

Ozet:
Admin shell ve ayarlar navigasyonu daha kurumsal bir kabuk yapisina tasindi; branding alani guclendirildi ve ayarlar ekranina toggle edilebilir yardimci panel eklendi.

Temel degisiklikler:
- `layouts.app` daha guclu header hiyerarsisi, iyilestirilmis sidebar spacing'i ve buyutulmus Lider branding alani ile guncellendi.
- `Ayarlar` ekrani daha net bir alt navigasyon, bolum bazli aciklamalar ve sag yardimci panel ile yeniden duzenlendi.
- Sag yardimci panel vanilla JS ile acilip kapanabilir hale getirildi ve kullanici tercihi localStorage uzerinde korunmaya baslandi.
- Ayarlar sayfasinda `?tab=` deep-link, validation ve save sonrasi ayni bolume donme davranisi korundu.
- Admin shell/navigation iyilestirmeleri icin feature testleri genisletildi.

Sema degisiklikleri:
- Bu surumde yeni migration yok.

Operasyon notlari:
- Sag yardimci panel varsayilan olarak genis ekranda acik, dar ekranda kapalidir.
- Bu pass yalniz admin shell/navigation yuzeyini iyilestirir; mevcut Mikro, mail, Spaces ve update akislarini degistirmez.
- Frontend degisiklikleri icin build alinmadan production gorunumu guncellenmez.

## [1.6.0] - 2026-03-25

Ozet:
Admin ayarlar ekrani ic sekmelere ayrildi, mevcut persistence korunarak guvenli mail sunucusu yonetimi ve admin-only baglanti testi eklendi.

Temel degisiklikler:
- `Ayarlar` ekrani `Guncellemeler`, `Depolama / Spaces`, `Mikro API`, `Mail / Exchange` ve `Genel Sistem` sekmelerine ayrildi.
- Settings deep-link davranisi `?tab=` ile korunacak sekilde save, validation ve test aksiyonlari ayni aktif sekmeye donduruldu.
- `system_settings` uzerinden `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` runtime override destegi eklendi.
- Mail kullanici adi ve sifresi plaintext render edilmeden, bos birakildiginda mevcut degeri koruyacak sekilde guvenli hale getirildi.
- Admin-only `Baglantiyi Test Et` aksiyonu ve ayni mail pipeline uzerinden calisan test mail akisi guclendirildi.

Sema degisiklikleri:
- Bu surumde yeni migration yok.

Operasyon notlari:
- Bu pass yalniz runtime-safe mail sunucusu alanlarini admin paneline tasir; `MAIL_MAILER`, `MAIL_URL`, `MAIL_SCHEME` gibi bootstrap detaylari env tarafinda kalir.
- Mail connection testi baglanti/auth seviyesinde kontrol saglar, teslimat garantisi vermez.
- Queue worker calismiyorsa test mail ve yeni siparis bildirimleri kuyrukta bekler.

## [1.5.0] - 2026-03-25

Ozet:
Exchange/SMTP uyumlu yeni siparis mail bildirimleri, admin tarafindan yonetilebilir runtime ayarlar ve guvenli test mail akisi mevcut Mikro siparis senkronizasyonu bozulmadan eklendi.

Temel degisiklikler:
- Admin ayarlar ekranina Grafik Departmani alicilari, CC/BCC, konu sablonu, override from ve test alicisi alanlari eklendi.
- Yeni siparis bildirimi yalniz Mikro sync ile ilk kez olusan siparislerde, commit sonrasi queue job olarak tetiklenecek sekilde baglandi.
- `SendNewOrderNotificationJob` ve `SendMailNotificationTestJob` ile mail gonderimi kullanici/sync request'inden ayrildi.
- Yeni siparis ve test mail akislarina ait basari, skip ve failure durumlari audit log ile gorunur hale getirildi.
- Mail ayarlari persistence, queue dispatch, disabled skip ve failure davranislari icin yeni feature testleri eklendi.

Sema degisiklikleri:
- Bu surumde yeni migration yok.

Operasyon notlari:
- Exchange icin Laravel SMTP ayarlari `.env` uzerinden yapilandirilir: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- Queue worker calismiyorsa otomatik yeni siparis bildirimi ve test mail isleri yalniz kuyruga yazilir.
- Mail disabled veya SMTP erisilemez olsa bile siparis sync akisi rollback olmaz.
- Native Exchange OAuth/EWS destegi bu surum kapsamina dahil edilmemistir; kurum SMTP/relay ayrintisi production ortaminda dogrulanmalidir.

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
