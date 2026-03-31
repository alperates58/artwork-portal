# Lider Portal — Kurulum ve Güncelleme Kılavuzu

Laravel 11 + Docker tabanlı Lider Portal için eksiksiz kurulum rehberi.
PHP, MySQL, Composer veya Node.js kurmanıza gerek yoktur. Docker her şeyi halleder.

---

## 1. Mimari Notlar

- MySQL container veritabanını hazır getirir; ayrıca veritabanı oluşturmanız gerekmez.
- `app` servisi PHP, Composer, Artisan ve update akışındaki otomatik frontend build adımlarını çalıştırır.
- `app` image içinde `nodejs` + `npm` bulunur; böylece Admin panelindeki "GitHub'dan Güncelle" aksiyonu `npm run build` çalıştırabilir.
- Ayrı `node` servisi manuel frontend derleme işlemleri için kullanılmaya devam eder.
- Redis zorunludur; cache, session ve queue için kullanılır.
- Setup wizard (`/setup`) ilk admin kullanıcısını ve `.env` son ayarlarını yazar.

---

## 2. Windows — Lokal Docker Kurulumu

### 2.1 Gereksinimler

- Docker Desktop (çalışır durumda)
- Git
- Windows PowerShell (yönetici olarak)

### 2.2 Repoyu İndir

```powershell
git clone https://github.com/alperates58/artwork-portal.git artwork-portal
cd artwork-portal
```

Proje zaten varsa:

```powershell
cd C:\Users\alper\Desktop\artwork-portal
```

### 2.3 CRLF Düzeltmesi — ZORUNLU

Docker Linux tabanlıdır. Windows'ta `entrypoint.sh` dosyası CRLF satır sonları ile kaydedilir ve container sürekli restart döngüsüne girer. Her `git clone` sonrasında mutlaka çalıştırın:

```powershell
(Get-Content docker\php\entrypoint.sh -Raw) -replace "`r`n", "`n" | Set-Content docker\php\entrypoint.sh -NoNewline
```

### 2.4 .env Oluşturma

```powershell
copy .env.example .env
notepad .env
```

Minimum doldurulması gerekenler:

```env
APP_NAME="Lider Portal"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Europe/Istanbul

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=artwork_portal
DB_USERNAME=portal_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=rootsecret

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=redissecret
REDIS_PORT=6379

FILESYSTEM_DISK=local
```

> DO Spaces bilgilerini şimdilik boş bırakabilirsiniz. Dosya yükleme çalışmaz ama uygulama ayağa kalkar.

### 2.5 .env Dosya İzni — ZORUNLU

Setup wizard kurulum sonunda `.env` dosyasını günceller. Yazma izni olmazsa 500 hatası alırsınız:

```powershell
# Windows'ta bu adım genellikle gerekmez ancak sorun çıkarsa:
# Docker Desktop → Settings → Resources → File Sharing aktif olmalı
```

### 2.6 Docker Build

```powershell
docker compose build
```

> İlk build 3-5 dakika sürebilir. Sonunda 4 image "Built" görmelisiniz.

### 2.7 Container'ları Başlat

```powershell
docker compose up -d
docker compose ps
```

Beklenen durum:

```
artwork_app     → Up
artwork_mysql   → Up (healthy)
artwork_nginx   → Up
artwork_redis   → Up
```

### 2.8 Composer Kurulumu — ZORUNLU timeout ayarı

`aws/aws-sdk-php` paketi büyük olduğundan varsayılan 300 saniyelik timeout aşılır. Önce timeout'u artırın:

```powershell
docker compose exec app composer config --global process-timeout 600
docker compose exec app composer install --no-interaction
```

### 2.9 Laravel Kurulum Adımları

```powershell
# 1. Config cache'i temizle (key:generate öncesi zorunlu)
docker compose exec app php artisan config:clear

# 2. APP Key oluştur
docker compose exec app php artisan key:generate

# 3. Config'i yeniden cache'le
docker compose exec app php artisan config:cache

# 4. Migration çalıştır
docker compose exec app php artisan migrate --force

# 5. Storage link oluştur
docker compose exec app php artisan storage:link

# 6. Tüm cache/route/view güncelle
docker compose exec app php artisan portal:update
```

> `config:clear` → `key:generate` → `config:cache` sırası kritiktir. Atlanırsa 500 "No application encryption key" hatası alırsınız.

### 2.10 Frontend Asset Build

```powershell
docker compose exec -u www-data app sh -lc "npm install --no-audit --no-fund && npm run build"
```

### 2.11 Nginx ve App Restart

İlk kurulumda nginx, app container IP'sini önbellekleyebilir ve 502 hatası verebilir:

```powershell
docker compose restart nginx app
```

### 2.12 Kurulum Doğrulama

```powershell
docker compose exec app php artisan about
```

Beklenen değerler:

```
Cache    → redis
Queue    → redis
Session  → redis
Config   → CACHED
```

Tarayıcıda `http://localhost` açın. Login ekranı gelmeli.

---

## 3. DigitalOcean Sunucu Kurulumu

### 3.1 Sistem Hazırlığı (Ubuntu 22.04 / 24.04)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y ca-certificates curl git ufw
curl -fsSL https://get.docker.com | sh
sudo apt install -y docker-compose-plugin
sudo usermod -aG docker $USER
newgrp docker
```

### 3.2 Güvenlik Duvarı

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 3.3 Repoyu İndir

```bash
git clone https://github.com/alperates58/artwork-portal.git /var/www/artwork-portal
cd /var/www/artwork-portal
```

### 3.4 CRLF Düzeltmesi — ZORUNLU

Windows'ta yazılmış dosyalar sunucuya geldiğinde CRLF sorunu taşır. Her kurulumda çalıştırın:

```bash
sed -i 's/\r//' docker/php/entrypoint.sh
```

### 3.5 .env Oluşturma ve İzin Ayarı

```bash
cp .env.example .env
nano .env
```

Production için minimum alanlar:

```env
APP_NAME="Lider Portal"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://SUNUCU_IP
APP_TIMEZONE=Europe/Istanbul

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=artwork_portal
DB_USERNAME=portal_user
DB_PASSWORD=GUCLU_SIFRE
DB_ROOT_PASSWORD=GUCLU_ROOT_SIFRE

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=GUCLU_REDIS_SIFRE
REDIS_PORT=6379

FILESYSTEM_DISK=local
```

**Setup wizard `.env` dosyasını yazabilmesi için izin verin:**

```bash
chmod 664 /var/www/artwork-portal/.env
chown root:www-data /var/www/artwork-portal/.env
```

> Bu adım atlanırsa setup wizard son adımda 500 hatası verir.

### 3.6 Docker Build ve Başlatma

```bash
docker compose build
docker compose up -d
docker compose ps
```

### 3.7 Composer Kurulumu

```bash
docker compose exec app composer config --global process-timeout 600
docker compose exec app composer install --no-interaction --no-dev --optimize-autoloader
```

### 3.8 Laravel Kurulum Adımları

```bash
# 1. Config cache temizle
docker compose exec app php artisan config:clear

# 2. APP Key oluştur
docker compose exec app php artisan key:generate

# 3. Config cache'le
docker compose exec app php artisan config:cache

# 4. Migration
docker compose exec app php artisan migrate --force

# 5. Storage link
docker compose exec app php artisan storage:link

# 6. Portal update
docker compose exec app php artisan portal:update
```

### 3.9 Frontend Asset Build

```bash
docker compose exec -u www-data app sh -lc "npm ci --no-audit --no-fund && npm run build"
```

### 3.10 Nginx ve App Restart

```bash
docker compose restart nginx app
```

### 3.11 Setup Wizard

Tarayıcıda `http://SUNUCU_IP/setup` adresini açın ve kurulumu tamamlayın.

### 3.12 Artwork Preview Doğrulaması â€” ZORUNLU

Artwork upload akışında `.ai`, `.eps`, `.pdf` ve `.psd` dosyaları için önizleme PNG dosyası queue üzerinden otomatik üretilir. Yeni kurulumdan sonra bu akışın gerçekten çalıştığını mutlaka doğrulayın:

```bash
docker compose ps
docker compose exec queue which gs
docker compose exec queue which magick
docker compose exec app php artisan migrate:status
```

Beklenen durum:

- `artwork_queue` → `Up`
- `artwork_scheduler` → `Up`
- `which gs` çıktı vermeli
- `which magick` çıktı vermeli
- `2026_03_31_210000_add_preview_fields_to_artwork_files` migration kaydı `Ran` olmalı

Bu doğrulama tamamlanmadan artwork preview akışı eksik çalışabilir.

### 3.13 Canlıya Alma Sonrası Son Adımlar

Kurulum bittiğinde ve site ilk kez açılır hale geldiğinde aşağıdaki adımları da çalıştırın:

```bash
cd /var/www/artwork-portal
docker compose build app queue scheduler
docker compose up -d --force-recreate app queue scheduler
docker compose exec app php artisan migrate --force
docker compose restart nginx
```

Notlar:

- `Dockerfile` değiştiğinde bu adım zorunludur; sadece `git pull` yeterli olmaz.
- `nginx` bazen eski `app` container IP'sini tutabilir; bu yüzden son adımda `docker compose restart nginx` önerilir.
- Bu adımlar tamamlandıktan sonra yeni kurulumdaki preview PNG akışı hazır olur.

---

## 4. Production .env Tam Yapılandırması

```env
APP_NAME="Lider Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.sirketiniz.com
APP_VERSION=1.9.1
APP_TIMEZONE=Europe/Istanbul
APP_INSTALLED=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=artwork_portal
DB_USERNAME=portal_user
DB_PASSWORD=GUCLU_SIFRE
DB_ROOT_PASSWORD=GUCLU_ROOT_SIFRE

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=GUCLU_REDIS_SIFRE
REDIS_PORT=6379

FILESYSTEM_DISK=spaces
DO_SPACES_KEY=xxx
DO_SPACES_SECRET=xxx
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=lider-portal-prod
DO_SPACES_URL=https://lider-portal-prod.fra1.digitaloceanspaces.com

MAIL_MAILER=smtp
MAIL_HOST=exchange.firma.local
MAIL_PORT=587
MAIL_USERNAME=portal@firma.com
MAIL_PASSWORD=MAIL_SIFRE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=portal@firma.com
MAIL_FROM_NAME="Lider Portal"

PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
```

---

## 5. Güvenli Update Akışı (Sunucu)

> Not: `3b010a6` ve sonrası sürümlerde "GitHub'dan Güncelle" akışı frontend asset build adımını otomatik çalıştırır.
> Bunun çalışabilmesi için app image'ın güncel olması gerekir.

```bash
cd /var/www/artwork-portal
git pull origin main
docker compose build app queue scheduler
docker compose up -d app queue scheduler
docker compose exec app npm -v
```

`npm -v` çıktı veriyorsa paneldeki update butonu frontend derlemeyi otomatik yapabilir.

```bash
cd /var/www/artwork-portal
git config --global --add safe.directory /var/www/artwork-portal
git fetch --all --prune
git pull origin main
docker compose build app
docker compose up -d --force-recreate app queue scheduler nginx
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec -u www-data app sh -lc "npm ci --no-audit --no-fund && npm run build"
docker compose exec app php artisan portal:update
```

---

## 6. Spaces Yapılandırması

- Bucket private kalmalıdır.
- İndirme akışı presigned URL ile çalışır; public URL kullanılmaz.
- `FILESYSTEM_DISK=spaces` olmadan yalnız Spaces bilgisi girmek yeterli değildir.

---

## 7. Sık Karşılaşılan Hatalar

### `exec entrypoint.sh: no such file or directory` — Container restart döngüsü

**Neden:** Windows CRLF satır sonu sorunu.

**Windows çözümü:**
```powershell
(Get-Content docker\php\entrypoint.sh -Raw) -replace "`r`n", "`n" | Set-Content docker\php\entrypoint.sh -NoNewline
docker compose down
docker compose build app
docker compose up -d
```

**Linux çözümü:**
```bash
sed -i 's/\r//' docker/php/entrypoint.sh
docker compose restart app queue scheduler
```

---

### `502 Bad Gateway`

**Neden:** Nginx, app container'ının eski IP'sini önbelleklemiş.

**Çözüm:**
```bash
docker compose restart nginx app
```

Eğer sorun `app`, `queue` veya `scheduler` recreate sonrasında başladıysa şu akışı uygulayın:
```bash
docker compose up -d --force-recreate app queue scheduler
docker compose restart nginx
```

---

### `500 — No application encryption key`

**Neden:** Config cache'liyken `APP_KEY` boş kalmış.

**Çözüm:**
```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan key:generate
docker compose exec app php artisan config:cache
```

---

### `composer install` — timeout (aws/aws-sdk-php)

**Neden:** Büyük paket, varsayılan 300 saniyelik timeout aşılıyor.

**Çözüm:**
```bash
docker compose exec app composer config --global process-timeout 600
docker compose exec app composer install --no-interaction
```

---

### `500 — Setup wizard son adımda hata` (Permission denied .env)

**Neden:** Setup wizard `.env` dosyasını güncellemek istiyor ama yazma izni yok.

**Çözüm:**
```bash
chmod 664 .env
chown root:www-data .env
```

---

### `failed_jobs tablosu yok` — Queue worker crash

**Neden:** Migration eksik çalışmış.

**Çözüm:**
```bash
docker compose exec app php artisan migrate --force
docker compose restart queue scheduler
```

---

### `npm ERR! EACCES ... node_modules` — Güncelleme ekranında frontend build izni hatası

**Neden:** `node_modules` veya `.npm` dizinleri root sahipliğinde kalmıştır; update butonu `www-data` ile çalışırken yazamaz.

**Çözüm:**
```bash
cd /var/www/artwork-portal
docker compose exec -u root app sh -lc "mkdir -p /var/www/html/node_modules /var/www/.npm /var/www/.config /var/www/html/public/build && chown -R www-data:www-data /var/www/html/node_modules /var/www/.npm /var/www/.config /var/www/html/public/build"
docker compose exec -u www-data app sh -lc "npm install --no-audit --no-fund && npm run build"
docker compose restart app nginx
```

---

## 8. Sık Kullanılan Komutlar

```bash
docker compose up -d                        # Başlat
docker compose down                         # Durdur
docker compose ps                           # Durum
docker compose logs app --tail=50          # App logu
docker compose logs nginx --tail=50        # Nginx logu
docker compose exec app bash               # Container içine gir
docker compose restart app nginx           # Yeniden başlat
docker compose exec app php artisan about  # Uygulama durumu
docker compose exec app php artisan portal:update  # Cache + route + view güncelle
```

---

## 9. Son Doğrulama Listesi

Kurulum sonrası sırayla kontrol edin:

1. `http://localhost` veya `http://SUNUCU_IP` açılıyor mu?
2. Login ekranı geliyor mu?
3. Admin paneli açılıyor mu?
4. Supplier portal açılıyor mu?
5. Artwork galerisi açılıyor mu?
6. `docker compose ps` → queue ve scheduler Up mu?
7. `php artisan about` → Cache/Queue/Session redis mi?
8. `npm run build` başarılı mı?
9. `docker compose exec queue which gs` ve `which magick` çalışıyor mu?
10. AI/EPS/PDF/PSD artwork yüklemesinden sonra preview PNG oluşuyor mu?
