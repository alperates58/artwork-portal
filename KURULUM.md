# Lider Portal Kurulum ve Güncelleme

Bu doküman mevcut Laravel 11 + Docker deposunun gerçek durumuna göre hazırlanmıştır. Proje greenfield değildir; setup wizard, admin paneli, supplier portalı, Redis, queue, scheduler ve Spaces desteği zaten vardır.

## 1. Lokal Docker kurulumu

```powershell
git clone <repo-url> artwork-portal
cd artwork-portal
Copy-Item .env.example .env
powershell -ExecutionPolicy Bypass -File .\scripts\setup.ps1
```

Alternatif günlük komutlar:

```powershell
.\scripts\dev.ps1 up
.\scripts\dev.ps1 logs
.\scripts\dev.ps1 shell
.\scripts\dev.ps1 migrate
.\scripts\dev.ps1 clear
```

İlk açılış:

- Uygulama: `http://localhost`
- Setup wizard gerekiyorsa: `http://localhost/setup`

Önerilen lokal `.env` notları:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `FILESYSTEM_DISK=local`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`
- `APP_SLOW_REQUEST_THRESHOLD_MS=800` yalnızca yavaşlık analizi gerektiğinde açılmalı

## 2. Production Droplet kurulumu

Sunucu hazırlığı:

```bash
sudo apt update
sudo apt install -y ca-certificates curl git
curl -fsSL https://get.docker.com | sh
sudo apt install -y docker-compose-plugin
sudo usermod -aG docker $USER
```

Kod kurulumu:

```bash
git clone <repo-url> /var/www/artwork-portal
cd /var/www/artwork-portal
cp .env.example .env
```

Production `.env` için kritik alanlar:

- `APP_NAME="Lider Portal"`
- `APP_URL=https://portal.ornekdomain.com`
- `APP_INSTALLED=true`
- MySQL ve Redis şifreleri
- `FILESYSTEM_DISK=spaces`
- tüm `DO_SPACES_*` alanları
- `MIKRO_ERP_URL`, `MIKRO_ERP_KEY`, gerekiyorsa `MIKRO_SHIPMENT_ENDPOINT`
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=0`
- `APP_SLOW_REQUEST_THRESHOLD_MS=800`

Container başlatma:

```bash
docker compose build app mysql
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan portal:update
```

Not:

- İlk kurulum wizard ile yapılacaksa geçici olarak `APP_INSTALLED=false` kullanılabilir.
- Mevcut sistem taşınıyorsa hazır `.env` + `portal:update` akışı daha kontrollüdür.

## 3. Ayarlar ve sınırlar

`.env` içinde kalması gerekenler:

- `APP_KEY`
- container/DB/Redis erişim host bilgileri
- ilk bootstrap için gerekli temel varsayılanlar

Admin panelinden yönetilebilen runtime ayarlar:

- Spaces disk seçimi ve bağlantı alanları
- Mikro base URL, API key, sevk endpoint yolu, sync aralığı

Bu runtime ayarlar `system_settings` tablosunda tutulur ve çalışma zamanında `.env` değerlerinin üzerine yazabilir. İlk ayağa kalkış için `.env` yine gereklidir.

## 4. Güncelleme akışı

GitHub’dan yeni commit geldikten sonra tam kurulum tekrar çalıştırılmaz.

Önerilen update sırası:

```bash
cd /var/www/artwork-portal
git pull
docker compose build app
docker compose up -d
docker compose exec app php artisan portal:update
```

`portal:update` şunları yapar:

- `migrate --force`
- `storage:link`
- `optimize:clear`
- `queue:restart`
- production ortamında `config:cache`, `route:cache`, `view:cache`

Bu sayede veri korunur, yeni migration’lar uygulanır ve çalışan portal yeniden kurulum istemeden güncellenir.

## 5. Queue, scheduler ve cache

Production sonrası kontrol:

```bash
docker compose ps
docker compose logs -f app nginx queue scheduler
docker compose exec app php artisan about
```

Beklenen durum:

- cache `redis`
- queue `redis`
- session `redis`
- `queue` servisi çalışıyor
- `scheduler` servisi çalışıyor

## 6. Spaces ve güvenli dosya akışı

- `FILESYSTEM_DISK=spaces` olmadan Spaces aktif olmaz
- tüm `DO_SPACES_*` alanları dolu olmalıdır
- dosyalar public açılmaz
- indirme akışı güvenli endpoint + presigned URL üzerinden çalışır

## 7. Operasyonel notlar

Türkçe karakter problemi görünürse:

- kaynak dosyaların UTF-8 kaydedildiğini doğrulayın
- container yeniden build edin
- tarayıcı cache temizleyin

Admin ekranları yavaşsa:

- Windows Docker bind mount gecikmesi hâlâ hissedilebilir
- `APP_SLOW_REQUEST_THRESHOLD_MS=800` ile log toplayın
- `docker compose logs -f mysql` ile slow query logunu izleyin

Frontend ilk yükleme gecikmesi:

- proje hâlâ Tailwind Play CDN kullanıyor
- bu nedenle production ilk yüklemede tarayıcı tarafında ek gecikme ve dış CDN bağımlılığı devam eder

## 8. Son kontrol listesi

1. Login çalışıyor mu
2. Admin panel açılıyor mu
3. Supplier portal açılıyor mu
4. Sipariş görüntüleme ve düzenleme çalışıyor mu
5. Upload/download akışı çalışıyor mu
6. Queue job’ları işleniyor mu
7. `portal:update` sonrası sistem yeniden kurulum istemeden açılıyor mu
