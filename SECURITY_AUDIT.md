# SECURITY_AUDIT

Tarih: 2026-04-04
Repo: `artwork-portal`
Kapsam: Yalnızca yerel depo, statik analiz, güvenli yapılandırma incelemesi, test ve güvenli doğrulama

## 1. Yönetici Özeti

Bu incelemede tedarikçi portalının ana sipariş ve indirme akışlarında belirgin bir supplier-to-supplier kırılımı doğrulanmadı. `accessibleSupplierIds()` ve `canDownloadForSupplier()` kullanımı kritik akışlarda genel olarak doğru.

Buna karşılık, dört yüksek önem seviyeli sorun doğrulandı:

1. İç kullanıcılar için sipariş görüntüleme yetkisi backend tarafında fiilen by-pass edilebiliyor.
2. Pasife alınmış kullanıcılar mevcut Sanctum tokenları ile API erişimini sürdürebiliyor.
3. Kurulum sihirbazı, kurulum işaretlerinden biri kaybolursa yeniden açılabiliyor.
4. Galeri önizleme/indirme uçları, `gallery.view` yetkisini zorlamıyor.

Ek olarak parola sıfırlama, token yaşam döngüsü, aktif revizyon bütünlüğü ve preview iş hattında orta seviye savunma boşlukları mevcut.

## 2. 100 Üzerinden Puan

Genel güvenlik puanı: **62/100**

Kısa yorum:

- Artı taraf: supplier izolasyonunun ana akışları büyük ölçüde doğru, SVG inline preview kısıtı mantıklı, XXE savunmaları mevcut.
- Eksi taraf: birden fazla doğrulanmış yüksek seviye yetkilendirme ve kimlik yaşam döngüsü sorunu var.
- Bu puan resmi bir standart skoru değil; bu repo için savunmacı, kanıt bazlı bir durum puanıdır.

## 3. Saldırı Yüzeyi Haritası

- Herkese açık uçlar:
  - `/login`
  - `/forgot-password`
  - `/reset-password/*`
  - `/api/v1/auth/token`
  - `/setup/*`
- Auth gerektiren supplier yüzeyi:
  - `portal.orders.*`
  - supplier preview/download
  - approval akışı
- Auth gerektiren internal yüzey:
  - sipariş liste/detay/satır ekranları
  - artwork yükleme/aktivasyon/silme
  - galeri preview/download
  - search endpoint
- Admin yüzeyi:
  - ayarlar
  - update/deploy
  - veri aktarımı
  - ERP/GitHub/Mikro test/sample uçları
  - kullanıcı/yetki yönetimi
- Asenkron ve depolama sınırları:
  - Redis session/queue
  - Spaces/local storage
  - Ghostscript/ImageMagick preview üretimi
  - GitHub/Mikro/Mail dış çağrıları
  - setup akışında `.env` yazımı

## 4. Bulgular

### High

#### 4.1 İç Kullanıcı Sipariş Görüntüleme Yetkisi Backend Tarafında By-pass Edilebiliyor

- Durum: **Confirmed**
- Severity: **High**
- Etkilenen dosyalar/metotlar:
  - `app/Policies/OrderPolicy.php` → `viewAny()`, `view()`
  - `app/Http/Controllers/Order/OrderController.php` → `index()`, `show()`
  - `app/Http/Controllers/Order/OrderLineController.php` → `show()`
  - `app/Http/Controllers/Admin/PermissionsController.php`
  - `resources/views/layouts/app.blade.php`
- Riskli mantık:
  - Yetki sistemi `orders.view` diye bir izin tanımlıyor.
  - Sidebar bu izin yoksa menüyü gizliyor.
  - Ancak `OrderPolicy::viewAny()` doğrudan `true`, `OrderPolicy::view()` de supplier değilse doğrudan `true` dönüyor.
  - `OrderController::index()`, `show()` ve `OrderLineController::show()` içinde görünüm yetkisi zorlanmıyor.
- Gerçekçi savunma senaryosu:
  - İç kullanıcı için sipariş görünürlüğü yönetim ekranından kapatılır.
  - Kullanıcı menüde link görmez ama doğrudan URL ile tüm siparişleri ve tedarikçi ilişkili verileri açabilir.
- İş etkisi:
  - İç tarafta supplier verileri, sipariş notları, revizyon geçmişi ve operasyonel bilgiler gereksiz kullanıcıya açılır.
- Önerilen düzeltme:
  - `OrderPolicy::viewAny()` ve `view()` iç kullanıcılar için `hasPermission('orders', 'view')` zorlamalı.
  - `index()`, `show()` ve `OrderLineController::show()` içinde açık `authorize` kontrolü eklenmeli.
  - Bu akış için regresyon testleri yazılmalı.

#### 4.2 Pasif Kullanıcılar Eski Sanctum Tokenlarla API Erişimine Devam Edebiliyor

- Durum: **Confirmed**
- Severity: **High**
- Etkilenen dosyalar/metotlar:
  - `routes/api.php`
  - `app/Http/Middleware/EnsureUserIsActive.php`
- Riskli mantık:
  - `is_active` kontrolü yalnızca token üretiminde var.
  - API route grubu yalnızca `auth:sanctum` ile korunuyor.
  - `EnsureUserIsActive` yalnızca web/session akışında çalışıyor.
- Gerçekçi savunma senaryosu:
  - Pasife alınan bir supplier veya iç kullanıcı daha önce aldığı bearer token ile API çağrılarına devam eder.
- İş etkisi:
  - Offboarding sonrası veri erişimi sürer.
  - Sipariş ve indirme URL’si üretimi gibi hassas API akışları etkilenir.
- Önerilen düzeltme:
  - API için ayrı bir `EnsureApiUserIsActive` middleware eklenmeli.
  - Kullanıcı pasife alındığında tüm `personal_access_tokens` silinmeli.
  - Şifre sıfırlamada da tokenlar iptal edilmeli.

#### 4.3 Setup Wizard, Kurulum İşaretlerinden Biri Kaybolursa Yeniden Açılabiliyor

- Durum: **Confirmed**
- Severity: **High**
- Etkilenen dosyalar/metotlar:
  - `routes/setup.php`
  - `app/Http/Middleware/RedirectIfSetupComplete.php` → `isInstalled()`
  - `app/Http/Controllers/Setup/SetupController.php` → `saveAdmin()`, `writeEnv()`, `appendEnvFlag()`
- Riskli mantık:
  - Kurulum tamamlandı sayılması için hem `storage/app/.setup_complete` hem `APP_INSTALLED=true` gerekiyor.
  - Bu iki işaretten biri kaybolursa `/setup` anonim olarak tekrar açılıyor.
- Gerçekçi savunma senaryosu:
  - Restore, storage temizliği veya env drift sonrası setup tekrar erişilebilir hale gelir.
  - Yeni admin oluşturulabilir ve `.env` tekrar yazılabilir.
- İş etkisi:
  - Uygulama tam ele geçirilebilir.
- Önerilen düzeltme:
  - Production’da setup varsayılan olarak kapalı olmalı.
  - Sadece tek seferlik, sunucu tarafı bir enable flag ile açılmalı.
  - Ayrıca kullanıcı/tablo varlığı gibi ikinci fail-closed kontroller eklenmeli.

#### 4.4 Galeri Preview/Download Uçları `gallery.view` Yetkisini Zorlamıyor

- Durum: **Confirmed**
- Severity: **High**
- Etkilenen dosyalar/metotlar:
  - `routes/web.php`
  - `app/Http/Controllers/Artwork/ArtworkGalleryPreviewController.php` → `__invoke()`
  - `app/Http/Controllers/Artwork/ArtworkGalleryDownloadController.php` → `__invoke()`
  - `app/Http/Controllers/Admin/ArtworkGalleryController.php`
- Riskli mantık:
  - Galeri yönetim ekranı `gallery.view` ve `gallery.manage` kontrol ediyor.
  - Ama doğrudan preview/download uçları yalnızca `isInternal()` kontrol ediyor.
- Gerçekçi savunma senaryosu:
  - Galeri izni kaldırılmış bir iç kullanıcı, doğrudan ID ile dosyayı yine alabilir.
- İş etkisi:
  - Yetkisi kaldırılmış personele artwork dosyaları sızabilir.
- Önerilen düzeltme:
  - Galeri için ortak policy oluşturulmalı.
  - Preview/download uçları da aynı policy üzerinden yetki kontrolü yapmalı.

### Medium

#### 4.5 Parola Sıfırlama Akışı Kullanıcı Enumerasyonu Yapıyor ve Route Rate Limit’i Yok

- Durum: **Confirmed**
- Severity: **Medium**
- Etkilenen dosyalar/metotlar:
  - `routes/web.php`
  - `app/Http/Controllers/Auth/PasswordResetController.php` → `sendReset()`
- Riskli mantık:
  - Kayıtlı olmayan e-posta için farklı hata mesajı dönüyor.
  - `/forgot-password` POST route’u throttled değil.
- Gerçekçi savunma senaryosu:
  - Hesap var/yok ayrımı dışarıdan ölçülebilir.
  - Çok sayıda reset denemesi üretilebilir.
- İş etkisi:
  - Hesap keşfi kolaylaşır, auth yüzeyi zayıflar.
- Önerilen düzeltme:
  - Her durumda aynı genel başarı mesajı dönülmeli.
  - Route seviyesinde throttle eklenmeli.

#### 4.6 Sanctum Token Yaşam Döngüsü Zayıf

- Durum: **Confirmed**
- Severity: **Medium**
- Etkilenen dosyalar/metotlar:
  - `routes/api.php`
  - `database/migrations/2024_01_01_000011_create_personal_access_tokens_table.php`
  - `app/Http/Controllers/Auth/PasswordResetController.php`
- Riskli mantık:
  - Tokenlar abilities ve expiry olmadan oluşturuluyor.
  - Password reset sonrası token iptali yok.
- Gerçekçi savunma senaryosu:
  - Ele geçirilmiş bir token uzun süre yaşamaya devam eder.
- İş etkisi:
  - API erişimi beklenenden uzun süre açık kalır.
- Önerilen düzeltme:
  - Token expiry tanımlanmalı.
  - Yetki bazlı abilities kullanılmalı.
  - Şifre reset ve kullanıcı pasife alma olaylarında tokenlar silinmeli.

#### 4.7 Aktif Revizyon Bütünlüğü Race Condition’a Açık

- Durum: **Confirmed**
- Severity: **Medium**
- Etkilenen dosyalar/metotlar:
  - `app/Models/Artwork.php` → `activateRevision()`
  - `app/Services/ArtworkUploadService.php` → `createRevision()`
  - `database/migrations/2024_01_01_000004_create_artworks_table.php`
- Riskli mantık:
  - Aktif revizyon flip işlemleri satır kilidi olmadan yapılıyor.
  - Veritabanında “bir artwork için tek aktif revizyon” kuralını garantileyen güçlü bir constraint yok.
- Gerçekçi savunma senaryosu:
  - Eşzamanlı aktivasyon/yükleme sonrası birden fazla aktif revizyon ya da yanlış `active_revision_id` oluşabilir.
- İş etkisi:
  - Yanlış dosya gösterimi/indirmesi, audit tutarsızlığı, iş akışı hatası.
- Önerilen düzeltme:
  - `lockForUpdate()` ile transaction bazlı güncelleme yapılmalı.
  - DB seviyesinde tek-aktif kuralı zorlanmalı.

#### 4.8 Preview Pipeline Güvenilmeyen Dosyaları Güçlü Dönüştürücülere Veriyor

- Durum: **Possible Risk**
- Severity: **Medium**
- Etkilenen dosyalar/metotlar:
  - `app/Http/Requests/ArtworkUploadRequest.php`
  - `app/Services/PortalSettings.php` → `allowedArtworkValidationRule()`
  - `app/Services/ArtworkPreviewGenerator.php` → `supports()`, `runConversion()`
  - `app/Jobs/GenerateArtworkPreviewJob.php`
- Riskli mantık:
  - Uygun uzantıdaki dosyalar queue worker içinde Ghostscript/ImageMagick’e veriliyor.
  - `-dSAFER` iyi bir adım ama izolasyon ayrı bir güvenlik sınırında değil.
- Gerçekçi savunma senaryosu:
  - Kötü biçimlenmiş tasarım dosyası converter process’ini çökertir veya worker üzerinde beklenmeyen etki yaratır.
- İş etkisi:
  - Preview worker DoS olur ya da daha yüksek etkili bir parser zafiyeti varsa uygulama sınırına yaklaşılır.
- Önerilen düzeltme:
  - Preview üretimi ayrı, sertleştirilmiş worker/container’da çalıştırılmalı.
  - Preview üretilebilen dosyalara daha sıkı boyut ve kaynak sınırı konmalı.

### Low

#### 4.9 Ham Exception Mesajları Admin Yüzüne Geri Basılıyor

- Durum: **Confirmed**
- Severity: **Low**
- Etkilenen dosyalar/metotlar:
  - `app/Http/Controllers/Admin/SettingsController.php` → `commits()`, `mikroViewSample()`
  - `app/Http/Controllers/Admin/SupplierController.php` → `import()`
  - `app/Http/Controllers/Admin/StockCardController.php` → `import()`
- Riskli mantık:
  - Kullanıcıya doğrudan `getMessage()` döndürülüyor.
- Gerçekçi savunma senaryosu:
  - Backend hata ayrıntıları, servis URL’leri veya iç işleme ipuçları UI’de görünür hale gelir.
- İş etkisi:
  - İç detay sızıntısı ve sosyal mühendislik yüzeyi artar.
- Önerilen düzeltme:
  - Ayrıntı log’a gitmeli, kullanıcıya genel Türkçe hata mesajı dönülmeli.

#### 4.10 HTTPS/Cookie Sertleştirmesi ve Bootstrap Secret’lar Varsayılan Olarak Güçlü Değil

- Durum: **Confirmed Hardening Gap**
- Severity: **Low**
- Etkilenen dosyalar/metotlar:
  - `config/session.php`
  - `docker/nginx/nginx.conf`
  - `.env.example`
  - `app/Http/Controllers/Setup/SetupController.php`
  - `docker-compose.yml`
- Riskli mantık:
  - `SESSION_SECURE_COOKIE` zorunlu değil.
  - HTTP listener aktif, HTTPS redirect yorum satırında.
  - `.env.example` ve setup akışı öngörülebilir servis secret’ları kullanıyor.
- Gerçekçi savunma senaryosu:
  - Zayıf kurulum yapan ortamlar güvensiz transport veya kopyalanmış secret ile ayağa kalkar.
- İş etkisi:
  - Session güvenliği ve çevresel sertleştirme zayıflar.
- Önerilen düzeltme:
  - Production varsayılanları güvenli hale getirilmeli.
  - Setup aşamasında rastgele secret üretilmeli.

## 5. Top 10 Fixes First

1. API’de pasif kullanıcı tokenlarını anında düşür.
2. `orders.view` kontrolünü backend’de gerçekten zorla.
3. Galeri preview/download için ortak policy uygula.
4. Setup wizard’ı production’da fail-closed yap.
5. Forgot-password akışını generic response + throttle ile düzelt.
6. Sanctum token expiry ve revoke akışlarını ekle.
7. Aktif revizyon geçişini race-safe hale getir.
8. Preview worker’ı izole et.
9. Admin UI’de ham exception mesajlarını kaldır.
10. HTTPS ve secure cookie varsayılanlarını sertleştir.

## 6. Önerilen Minimal Patch Yaklaşımı

- `routes/api.php` içine `auth:sanctum` yanında aktif kullanıcı middleware’i ekle.
- Kullanıcı pasife alındığında ve şifre sıfırlandığında `tokens()->delete()` çalıştır.
- `OrderController::index/show`, `OrderLineController::show` için yetki kontrolü ekle.
- `OrderPolicy::viewAny/view` içinde iç kullanıcılar için `orders.view` zorunlu olsun.
- Galeri preview/download controller’larında `gallery.view` zorunlu olsun.
- `RedirectIfSetupComplete::isInstalled()` içine ikinci fail-closed sinyal ekle.
- `PasswordResetController::sendReset()` içinde her durumda aynı mesajı dön.
- `routes/web.php` üzerinde forgot-password route’una throttle ekle.
- Revizyon aktivasyonlarında satır kilidi ve DB integrity guard kullan.
- Preview üretimini ayrı worker/container’a taşı.

## 7. Eksik Güvenlik Testleri

- `orders.view` kaldırılmış kullanıcı için `orders.index/show/order-lines.show` 403 testi
- `gallery.view` kaldırılmış kullanıcı için preview/download 403 testi
- Pasife alınmış kullanıcı tokenının API’den reddedilme testi
- Password reset sonrası token iptal testi
- Forgot-password generic response testi
- Forgot-password throttle testi
- Concurrent revision activation bütünlük testi
- Setup route’un initialized instance’ta fail-closed testi
- Admin import/update uçlarında ham exception metni dönmeme testi

## 8. Near Misses ve Olumlu Kontroller

- Supplier portal sipariş ve indirme akışları genel olarak doğru supplier izolasyonu kullanıyor.
- SVG orijinal dosyaların inline preview edilmemesi yönündeki yaklaşım doğru.
- XML import tarafında XXE/DTD savunmaları mevcut görünüyor.
- Deploy tarafında shell string interpolation yerine argüman dizileri kullanılması olumlu.

## 9. Doğrulama Notu

Güvenli yerel doğrulama kapsamında `docker compose exec app php artisan test` çalıştırıldı.

- Sonuç: 194 test, 597 assertion
- Durum: 12 başarısız, 1 skipped

Bu nedenle mevcut test paketi şu anda tam güvenilir bir security regression kapısı değil. Yeni güvenlik testleri eklenmeden önce kırmızı testlerin stabilize edilmesi gerekir.
