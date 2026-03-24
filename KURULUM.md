# Kurulum ve Deployment

Bu doküman repo içindeki mevcut Laravel 11 + Docker yapısına göre hazırlanmıştır. Uygulama greenfield değildir; setup wizard, Docker servisleri, Redis, queue, scheduler ve Spaces desteği zaten kod tabanında mevcuttur.

## 1. Gereksinimler

Lokal:

- Docker Desktop
- Docker Compose v2
- Git

Production Droplet:

- Ubuntu 22.04 veya 24.04
- Docker Engine
- Docker Compose plugin
- Alan adı ve TLS sertifikası
- DigitalOcean Spaces bilgileri

## 2. Lokal Docker Kurulumu

### Windows PowerShell

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

### Make kullanan ortamlar

```bash
cp .env.example .env
make setup
```

### İlk açılış

- Uygulama: `http://localhost`
- Setup wizard: `http://localhost/setup`

Setup wizard aktifteyse 4 adım çalışır:

1. Site ayarları
2. Veritabanı bağlantısı
3. DigitalOcean Spaces
4. Admin kullanıcı

Kurulum tamamlanınca `.env` içine `APP_INSTALLED=true` yazılır ve `storage/app/.setup_complete` oluşturulur.

## 3. Lokal `.env` Önerisi

Repo artık lokal için de Redis tabanlı ayarla çalışacak şekilde hizalanmıştır:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost
APP_LOCALE=tr

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=artwork_portal
DB_USERNAME=portal_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=rootsecret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_CLIENT=predis
REDIS_PASSWORD=redissecret
REDIS_PORT=6379

FILESYSTEM_DISK=local

PHP_OPCACHE_VALIDATE_TIMESTAMPS=1
PHP_OPCACHE_REVALIDATE_FREQ=2
APP_SLOW_REQUEST_THRESHOLD_MS=0
```

Notlar:

- Lokal geliştirmede kod değişikliklerinin anında görünmesi için `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1` bırakılmalıdır.
- Yavaş istek logu almak isterseniz `APP_SLOW_REQUEST_THRESHOLD_MS=800` gibi bir değer verin. Loglar `storage/logs/laravel.log` içine `slow_request` olarak düşer.

## 4. Production Droplet Kurulumu

### Sunucu hazırlığı

```bash
sudo apt update
sudo apt install -y ca-certificates curl git
curl -fsSL https://get.docker.com | sh
sudo apt install -y docker-compose-plugin
sudo usermod -aG docker $USER
```

Yeni oturum açtıktan sonra:

```bash
git clone <repo-url> /var/www/artwork-portal
cd /var/www/artwork-portal
cp .env.example .env
```

### Production `.env`

En az şu değerleri doldurun:

```env
APP_NAME="Lider Tedarikçi Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.ornekdomain.com
APP_LOCALE=tr
APP_INSTALLED=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=artwork_portal
DB_USERNAME=portal_user
DB_PASSWORD=guclu-sifre
DB_ROOT_PASSWORD=daha-guclu-root-sifre

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_CLIENT=predis
REDIS_PASSWORD=guclu-redis-sifre
REDIS_PORT=6379

FILESYSTEM_DISK=spaces
DO_SPACES_KEY=...
DO_SPACES_SECRET=...
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=bucket-adi
DO_SPACES_URL=https://bucket-adi.fra1.digitaloceanspaces.com

ARTWORK_DOWNLOAD_TTL=15

PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
PHP_OPCACHE_REVALIDATE_FREQ=0
APP_SLOW_REQUEST_THRESHOLD_MS=800
```

### Container build ve başlatma

```bash
docker compose build app mysql
docker compose up -d
```

Production’da ilk kurulum yapacaksanız:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
```

Setup wizard kullanılacaksa `APP_INSTALLED=false` ile başlayıp `https://domain/setup` üzerinden tamamlayabilirsiniz. Mevcut çalışan kurulum taşınıyorsa genelde `APP_INSTALLED=true` ve hazır `.env` ile gitmek daha kontrollüdür.

## 5. DigitalOcean Spaces Notları

- `FILESYSTEM_DISK=spaces` olmadan Spaces aktif olmaz.
- `DO_SPACES_REGION`, `DO_SPACES_ENDPOINT`, `DO_SPACES_BUCKET`, `DO_SPACES_KEY`, `DO_SPACES_SECRET` birlikte doldurulmalıdır.
- Uygulama dosyaları public yapmaz; indirme presigned URL üzerinden yapılır.
- `SpacesStorageService` ve `MultipartUploadService` artık istemciyi lazy başlatır. Bu sayede eksik Spaces env yüzünden unrelated route’larda container boot anında çökme riski azalır.

## 6. Cache, Queue, Scheduler ve Optimize

Production için önerilen sıra:

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Queue ve scheduler zaten ayrı servis olarak compose içinde tanımlıdır:

- `queue`: `php artisan queue:work redis`
- `scheduler`: her 60 saniyede `php artisan schedule:run`

Kontrol:

```bash
docker compose ps
docker compose logs -f app nginx queue scheduler
```

## 7. Storage ve Dosya Notları

- Lokal disk kullanımı için `FILESYSTEM_DISK=local`
- Spaces kullanımı için `FILESYSTEM_DISK=spaces`
- Lokal disk download akışı yine güvenli endpoint üzerinden çalışır
- Production’da `public/storage` symlink yalnızca `public` disk için gerekir; artwork indirme akışı buna bağlı değildir

## 8. Migration ve Seed

Lokal:

```bash
docker compose exec app php artisan migrate --seed
```

Temiz kurulum:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Production’da seed işlemini sadece gerçekten gerekiyorsa çalıştırın:

```bash
docker compose exec app php artisan migrate --force
```

## 9. Sık Karşılaşılan Sorunlar

### Türkçe karakterler bozuk görünüyorsa

- Tarayıcı cache temizleyin
- Container’ları yeniden build edin: `docker compose build app mysql`
- Nginx charset ve PHP `default_charset` değişikliklerinin yüklendiğini doğrulayın
- Kaynak dosyaların UTF-8 olarak kaydedildiğini doğrulayın

### Redis devrede değilse

- `.env` içinde `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis` olduğundan emin olun
- `docker compose ps` içinde `redis` servisinin ayakta olduğunu kontrol edin
- `docker compose exec app php artisan about` ile driver’ları doğrulayın

### Spaces route’ları env eksikliğinden hata veriyorsa

- `FILESYSTEM_DISK=spaces` ise tüm `DO_SPACES_*` değerleri dolu olmalı
- Özellikle `DO_SPACES_REGION` boş bırakılmamalı

### Admin ekranları hâlâ yavaşsa

- Windows bind mount gecikmesi hâlâ etkili olabilir
- `APP_SLOW_REQUEST_THRESHOLD_MS=800` açıp loglara bakın
- `docker compose logs -f nginx app mysql` ile istek ve slow query davranışını izleyin

### MySQL özel ayarları yüklenmiyorsa

- Repo artık MySQL’i custom image ile build eder; `docker compose build mysql` sonrası tekrar ayağa kaldırın

## 10. Deployment Sonrası Kontrol Listesi

1. `docker compose ps` ile tüm servisler `Up` olmalı
2. `php artisan about` içinde:
   - cache `redis`
   - queue `redis`
   - session `redis`
3. Login çalışmalı
4. Admin panel açılmalı
5. Supplier portal açılmalı
6. Local veya Spaces download akışı test edilmeli
7. Queue job ve scheduler logları kontrol edilmeli

## 11. Önemli Uyarı

Uygulama fonksiyonel olarak Droplet + Spaces’a taşınabilir durumdadır; ancak frontend hâlâ Tailwind Play CDN kullandığı için production ilk yükleme süresinde ek browser-side gecikme ve dış CDN bağımlılığı vardır. Bu doküman mevcut repo durumunu anlatır; ayrı bir frontend asset pipeline çalışması yapılmadıkça bu davranış devam eder.
