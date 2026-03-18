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
# 1. Repoyu klonla
git clone https://github.com/KULLANICI/artwork-portal.git
cd artwork-portal

# 2. Tek komutla kur (ilk kurulum)
make setup

# 3. Tarayıcıda aç
open http://localhost
```

### Manuel kurulum

```bash
cp .env.example .env
# .env dosyasını düzenle (DO Spaces bilgilerini gir)

docker compose build
docker compose up -d

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

---

## Varsayılan Kullanıcılar

| Rol | E-posta | Şifre |
|---|---|---|
| Admin | admin@portal.local | Admin1234! |
| Grafik | grafik@portal.local | Grafik1234! |
| Satın Alma | satin.alma@portal.local | SatinAlma1234! |
| Tedarikçi | tedarikci@portal.local | Ted1234! |

> **Üretim ortamında tüm şifreleri değiştirin!**

---

## .env Yapılandırması

```env
# Uygulama
APP_URL=https://portal.sirketiniz.com

# DigitalOcean Spaces
DO_SPACES_KEY=xxxxx
DO_SPACES_SECRET=xxxxx
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=artwork-portal-prod

# Güvenli indirme linki geçerlilik süresi (dakika)
ARTWORK_DOWNLOAD_TTL=15
```

---

## Geliştirici Komutları

```bash
make up          # Başlat
make down        # Durdur
make logs        # Logları izle
make shell       # Container içine gir
make migrate     # Migration çalıştır
make fresh       # Taze kurulum (veri silinir!)
make clear       # Cache temizle
make cache       # Production cache
```

---

## DigitalOcean Production Deploy

```bash
# Sunucuda (Ubuntu 22.04)
curl -fsSL https://get.docker.com | sh
apt install docker-compose-plugin -y

git clone ... /var/www/artwork-portal
cd /var/www/artwork-portal

# SSL sertifikası
apt install certbot -y
certbot certonly --standalone -d portal.sirketiniz.com
cp /etc/letsencrypt/live/portal.sirketiniz.com/fullchain.pem docker/nginx/ssl/
cp /etc/letsencrypt/live/portal.sirketiniz.com/privkey.pem   docker/nginx/ssl/

# nginx.conf içindeki HTTPS bloğunu uncomment et

cp .env.example .env
# .env düzenle

make setup
make cache
```

---

## Proje Yapısı

```
app/
  Enums/           # UserRole, ArtworkStatus
  Http/
    Controllers/
      Auth/        # Login, PasswordReset
      Admin/       # User, Supplier, AuditLog
      Artwork/     # Artwork, Download
      Order/       # Order, OrderLine
      Portal/      # Tedarikçi portalı
    Middleware/    # CheckRole, EnsureUserIsActive
    Requests/      # ArtworkUploadRequest
  Models/          # User, Supplier, PurchaseOrder, ...
  Policies/        # OrderPolicy, ArtworkPolicy
  Services/        # SpacesStorageService, ArtworkUploadService, AuditLogService
database/
  migrations/
  seeders/
resources/views/
  layouts/         # app.blade.php
  auth/            # login, forgot-password, reset-password
  dashboard.blade.php
  orders/          # index, show, create, edit
  artworks/        # create, show, revisions
  portal/          # Tedarikçi ekranları
  admin/           # users, suppliers, logs
docker/
  nginx/
  php/
  mysql/
```

---

## Güvenlik

- Tüm dosyalar DigitalOcean Spaces'te **private** olarak tutulur
- İndirme linkleri 15 dakika geçerli **presigned URL** ile sağlanır
- Tedarikçiler sadece kendi siparişlerine erişebilir (Policy kontrolü)
- Tüm işlemler `audit_logs` tablosuna kaydedilir
- CSRF koruması tüm formlarda aktif (Laravel default)
- Pasif kullanıcılar sisteme giremez

---

## Faz 2 Planı

- [ ] Dashboard metrikleri genişletme
- [ ] E-posta bildirimleri (artwork yüklenince tedarikçiye)
- [ ] Tedarikçi "Gördüm / Onayladım" akışı
- [ ] Revizyon geçmişi karşılaştırma
- [ ] Gelişmiş log raporlama

## Faz 3 Planı

- [ ] Kalite dokümanları modülü
- [ ] Numune onay süreci
- [ ] ERP entegrasyonu (API endpoints)
- [ ] Toplu artwork aktarım
