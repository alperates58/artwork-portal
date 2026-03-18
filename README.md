# Artwork Portal

Tedarikçi Artwork Yönetim Sistemi — Laravel 11 + Docker + DigitalOcean Spaces

---

## Gereksinimler

- Docker Desktop (veya Docker Engine + Docker Compose v2)
- Git

Sunucuda PHP, MySQL, Composer kurmaya gerek yok. Docker her şeyi halleder.

---

## Hızlı Başlangıç

```bash
git clone https://github.com/alperates58/artwork-portal.git
cd artwork-portal
make setup
open http://localhost/setup
```

`make setup` şunları otomatik yapar:
1. `.env` oluşturur
2. Docker container'larını başlatır
3. `composer install`
4. `php artisan key:generate`
5. `php artisan migrate --seed`

---

## Setup Sihirbazı

İlk çalıştırmada `http://SUNUCU_IP/setup` adresi açılır:

| Adım | İçerik |
|---|---|
| 1 | Site ayarları (APP_NAME, APP_URL, timezone) |
| 2 | Veritabanı bağlantısı — canlı test |
| 3 | DigitalOcean Spaces — canlı test |
| 4 | Admin kullanıcı oluşturma + migrate |

Kurulum tamamlanınca setup adresi **otomatik kilitlenir** (çift koruma: lock dosyası + .env bayrağı).

---

## Varsayılan Kullanıcılar (seed sonrası)

| Rol | E-posta | Şifre |
|---|---|---|
| Admin | admin@portal.local | Admin1234! |
| Grafik | grafik@portal.local | Grafik1234! |
| Satın Alma | satin.alma@portal.local | SatinAlma1234! |
| Tedarikçi | tedarikci@portal.local | Ted1234! |

> **Üretim ortamında tüm şifreleri değiştirin!**

---

## Geliştirici Komutları

```bash
make up             # Başlat
make down           # Durdur
make logs           # Logları izle (app + nginx + queue)
make shell          # Container bash
make migrate        # Migration çalıştır
make fresh          # Taze kurulum (veri silinir)
make test           # Tüm testleri çalıştır
make test-unit      # Sadece unit testler
make test-feature   # Sadece feature testler
make erp-sync       # Mikro ERP sync çalıştır
make erp-sync-dry   # Dry-run (değişiklik yapmaz)
make clear          # Cache temizle
make cache          # Production cache
```

---

## .env Yapılandırması

```env
APP_URL=https://portal.sirketiniz.com
APP_INSTALLED=false         # setup wizard tamamlayınca true olur

DO_SPACES_KEY=xxxxx
DO_SPACES_SECRET=xxxxx
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=artwork-portal-prod

ARTWORK_DOWNLOAD_TTL=15     # Presigned URL süresi (dakika)

# Mikro ERP (Faz 2) — boş bırakılırsa mock veri kullanılır
MIKRO_ERP_URL=http://mikro-sunucu/api
MIKRO_ERP_KEY=api-key-buraya
MIKRO_SYNC_INTERVAL=60      # Dakika
```

---

## Testler

```bash
make test           # Tüm testler (SQLite in-memory)
make test-unit      # Unit: SpacesService path builder, model helpers
make test-feature   # Feature: auth, upload, download security, RBAC
```

Test ortamı SQLite kullanır — gerçek Spaces veya MySQL bağlantısı gerektirmez.

---

## API (Faz 3)

```bash
# Token al
POST /api/v1/auth/token
{ "email": "admin@portal.local", "password": "Admin1234!", "device_name": "ERP" }

# Sipariş listesi
GET /api/v1/orders
Authorization: Bearer {token}

# Sipariş detayı + aktif artwork
GET /api/v1/orders/PO-2024-001

# Güvenli indirme URL'si (15 dk geçerli)
GET /api/v1/artworks/{id}/download-url

# ERP'den sipariş push (sadece admin token)
POST /api/v1/orders
```

---

## DigitalOcean Production Deploy

```bash
# Ubuntu 22.04 Droplet (4GB RAM / 2vCPU önerilir)
curl -fsSL https://get.docker.com | sh
apt install docker-compose-plugin -y

git clone https://github.com/alperates58/artwork-portal.git /var/www/artwork-portal
cd /var/www/artwork-portal

# SSL
apt install certbot -y
certbot certonly --standalone -d portal.sirketiniz.com
cp /etc/letsencrypt/live/portal.sirketiniz.com/fullchain.pem docker/nginx/ssl/
cp /etc/letsencrypt/live/portal.sirketiniz.com/privkey.pem   docker/nginx/ssl/
# nginx.conf içindeki HTTPS bloğunu uncomment et

cp .env.example .env
# .env düzenle (DO Spaces, APP_URL vs.)

make setup
make cache

# Tarayıcıda:
# https://portal.sirketiniz.com/setup → kurulum sihirbazı
```

---

## Proje Yapısı

```
app/
  Enums/                    # UserRole, ArtworkStatus
  Http/
    Controllers/
      Auth/                 # Login, PasswordReset
      Admin/                # User, Supplier, AuditLog, ErpSync
      Artwork/              # Artwork, Download
      Order/                # Order, OrderLine
      Portal/               # Tedarikçi portalı
      Faz2/                 # ApprovalController
      Api/V1/               # REST API (Faz 3)
      Setup/                # Kurulum sihirbazı
    Middleware/             # CheckRole, EnsureUserIsActive, SetupMiddleware
    Requests/               # ArtworkUploadRequest
  Models/                   # User, Supplier, PurchaseOrder, ...
  Models/Faz3/              # QualityDocument, SampleApproval (placeholder)
  Policies/                 # OrderPolicy, ArtworkPolicy
  Services/
    SpacesStorageService    # DO Spaces — upload, presigned URL
    MultipartUploadService  # 1GB+ dosya desteği
    ArtworkUploadService    # Upload + revizyon yönetimi
    AuditLogService         # Loglama
    Faz2/
      ArtworkApprovalService # Onay akışı
      MikroErpSyncService   # ERP entegrasyon (legacy)
    Faz3/
      QualityDocumentService # Kalite dokümanları
      SampleApprovalService  # Numune onayı
    Erp/
      MikroErpService       # Güncel ERP servisi
  Jobs/
    Faz2/
      SyncErpOrdersJob       # ERP sync job
      SendArtworkNotificationJob # Bildirim job
  Mail/
    Faz2/
      ArtworkUploadedMail    # Tedarikçi bildirimi
database/
  migrations/               # 9 migration (Faz 1-2-3 hepsi)
  factories/                # User, Supplier, Order, Artwork factories
  seeders/
resources/views/
  setup/                    # 4 adım + tamamlandı ekranı
  layouts/                  # app.blade.php
  auth/                     # login, forgot, reset
  dashboard.blade.php
  orders/                   # index, show, create, edit
  artworks/                 # create, revisions
  portal/                   # Tedarikçi ekranları
  admin/                    # users, suppliers, logs
routes/
  web.php                   # Tüm web rotaları + Faz2
  api.php                   # REST API v1
  setup.php                 # Kurulum sihirbazı
  console.php               # Scheduler
tests/
  Feature/                  # Auth, Upload, Download, RBAC
  Unit/                     # SpacesService, ArtworkRevision
config/
  artwork.php               # Portal ayarları
  erp.php                   # ERP bağlantı ayarları
  filesystems.php           # DO Spaces disk
docker/
  nginx/nginx.conf
  php/Dockerfile, php.ini, opcache.ini
  mysql/my.cnf
```

---

## Faz Yol Haritası

### Faz 1 (MVP) — Tamamlandı
- Login + şifremi unuttum
- Rol yönetimi (Admin, Grafik, Satın Alma, Tedarikçi)
- Tedarikçi firma yönetimi + supplier_users pivot
- Sipariş + sipariş satırı yönetimi
- Artwork yükleme (DO Spaces — multipart 1GB+)
- Aktif revizyon yönetimi
- Güvenli indirme (presigned URL, 15 dk)
- Audit log (download_logs + view_logs ayrı tablolar)
- Setup sihirbazı (4 adım, çift kilit)
- Docker + Nginx + Redis + Queue deploy

### Faz 2 — Koda Dahil (Etkinleştirmeye hazır)
- Dashboard genişletilmiş metrikler
- E-posta bildirimleri (artwork yüklenince tedarikçiye)
- Tedarikçi "Gördüm / Onayladım" akışı
- Mikro ERP senkronizasyonu (`make erp-sync`)
- REST API v1 (token auth, sipariş/download endpoint'leri)
- Queue + scheduler altyapısı

### Faz 3 — İskelet Hazır
- Kalite dokümanları modülü (QualityDocument model + servis)
- Numune onay süreci (SampleApproval model + servis)
- ERP push API endpoint'i
- Teknik çizimler

---

## Güvenlik

- Tüm dosyalar Spaces'te **private** — public URL yok
- İndirme: 15 dk geçerli **presigned URL** (sunucudan geçmez)
- Tedarikçi izolasyonu: Policy + middleware çift kontrol
- `audit_logs` + ayrı `download_logs` / `view_logs` tabloları
- CSRF tüm formlarda aktif
- Pasif kullanıcı girişi engellenir
- Setup ekranı çift kilitli (lock dosyası + .env bayrağı)
