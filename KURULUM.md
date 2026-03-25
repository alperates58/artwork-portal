# Lider Portal Kurulum ve Güncelleme

Bu doküman mevcut Laravel 11 + Docker tabanlı Lider Portal reposu için hazırlanmıştır. Proje greenfield değildir; setup wizard, admin paneli, supplier portal, artwork/revision akışları, Redis ve Spaces desteği zaten vardır. Amaç mevcut çalışan davranışı koruyarak production kurulumu ve güncelleme sürecini güvenli hale getirmektir.

## 1. Mimari notlar

- Veritabanı oluşturma bugün altyapı seviyesindedir.
- Lokal Docker akışında MySQL container veritabanını hazır getirir.
- Setup wizard uygulama içi kurulum, admin kullanıcı ve bağlantı doğrulama rolünü sürdürür.
- Bu pass içinde setup wizard veritabanı oluşturan bir araca dönüştürülmemiştir.

## 2. Lokal Docker kurulumu

```powershell
git clone <repo-url> artwork-portal
cd artwork-portal
Copy-Item .env.example .env
powershell -ExecutionPolicy Bypass -File .\scripts\setup.ps1
```

Sık kullanılan komutlar:

```powershell
.\scripts\dev.ps1 up
.\scripts\dev.ps1 logs
.\scripts\dev.ps1 shell
.\scripts\dev.ps1 migrate
.\scripts\dev.ps1 assets-install
.\scripts\dev.ps1 assets-build
.\scripts\dev.ps1 clear
```

İlk açılış:

- Uygulama: `http://localhost`
- Setup wizard gerekiyorsa: `http://localhost/setup`

## 3. Asset build kararı

Bu repo artık asset build için `app` container’ına Node kurmaz.

Seçilen yaklaşım:

- `app` servisi yalnız PHP, Composer ve Artisan işleri içindir.
- Ayrı `node` servisi `npm ci` ve `npm run build` çalıştırır.
- Bu sayede production PHP runtime image’ına gereksiz Node/NPM yüklenmez.

Lokal komutlar:

```bash
docker compose run --rm node npm ci
docker compose run --rm node npm run build
```

Makefile karşılıkları:

```bash
make assets-install
make assets-build
```

## 4. DigitalOcean Droplet hazırlığı

Ubuntu 22.04 veya 24.04 önerilir.

```bash
sudo apt update
sudo apt install -y ca-certificates curl git ufw
curl -fsSL https://get.docker.com | sh
sudo apt install -y docker-compose-plugin
sudo usermod -aG docker $USER
newgrp docker
```

Temel güvenlik:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

Kod kurulumu:

```bash
git clone <repo-url> /var/www/artwork-portal
cd /var/www/artwork-portal
cp .env.example .env
```

## 5. Production `.env` zorunlu alanları

En az şu alanları doldurun:

- `APP_NAME="Lider Portal"`
- `APP_URL=https://portal.ornekdomain.com`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_VERSION=1.5.0`
- `APP_TIMEZONE=Europe/Istanbul`
- `APP_INSTALLED=true` veya ilk kurulum wizard kullanılacaksa geçici olarak `false`
- `DB_*` alanları
- `REDIS_*` alanları
- `FILESYSTEM_DISK=spaces`
- tüm `DO_SPACES_*` alanları
- gerekiyorsa `MIKRO_*` alanları
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=0`

## 6. Redis kurulumu ve beklenti

Production için Redis öneri değil, beklenen çalışma şeklidir.

Doğrulama:

```bash
docker compose ps
docker compose logs -f redis queue scheduler
docker compose exec app php artisan about
```

Beklenen durum:

- cache `redis`
- queue `redis`
- session `redis`
- `queue` servisi çalışıyor
- `scheduler` servisi çalışıyor

## 7. Spaces yapılandırması

Production için örnek:

```env
FILESYSTEM_DISK=spaces
DO_SPACES_KEY=xxx
DO_SPACES_SECRET=xxx
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=lider-portal-prod
DO_SPACES_URL=https://lider-portal-prod.fra1.digitaloceanspaces.com
```

Notlar:

- Bucket private kalmalıdır.
- İndirme akışı public URL üzerinden değil, yetkili endpoint + presigned URL ile çalışır.
- `FILESYSTEM_DISK=spaces` olmadan yalnız Spaces bilgisi girmek yeterli değildir.

## 8. Container build ve ilk ayağa kaldırma

```bash
cd /var/www/artwork-portal
docker compose build app
docker compose up -d --force-recreate app mysql redis queue scheduler nginx
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose run --rm node npm ci
docker compose run --rm node npm run build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan portal:update
```

İlk kurulum wizard kullanılacaksa:

- `.env` içinde geçici olarak `APP_INSTALLED=false`
- tarayıcıda `/setup`
- kurulum tamamlanınca uygulama kilitlenir

Mevcut çalışan kurulum taşınıyorsa:

- `APP_INSTALLED=true`
- hazır `.env`
- `migrate --force`
- `portal:update`

daha kontrollü akıştır.

## 9. Frontend asset build

Bu repo production dostu yerel asset hattı için `Vite + Tailwind` yapılandırması içerir.

Build sırası:

```bash
docker compose run --rm node npm ci
docker compose run --rm node npm run build
```

Notlar:

- Build çıktısı `public/build` altına yazılır.
- Build varsa uygulama yerel asset kullanır.
- Build yoksa yalnız geçici fallback olarak CDN yolu devreye girer.
- Production’da fallback’e güvenmeyin; mutlaka container içinden build alın.

## 10. Güvenli update akışı

GitHub kontrol komutu:

```bash
docker compose exec app php artisan portal:update:check
```

Uygulama update komutu:

```bash
docker compose exec app php artisan portal:update
```

Önerilen production update sırası:

```bash
cd /var/www/artwork-portal
git fetch --all --prune
git status
git pull origin main
docker compose build app
docker compose up -d --force-recreate app queue scheduler nginx
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose run --rm node npm ci
docker compose run --rm node npm run build
docker compose exec app php artisan portal:update
```

## 11. Son doğrulama listesi

1. Login çalışıyor mu
2. Admin panel açılıyor mu
3. Supplier portal açılıyor mu
4. Sipariş ve artwork akışları bozulmadı mı
5. Upload/download akışı çalışıyor mu
6. Redis, queue ve scheduler ayakta mı
7. `portal:update` sonrası sistem yeniden kurulum istemeden açılıyor mu
8. `docker compose run --rm node npm run build` başarılı mı
9. Built asset ile layout bozulmadan açılıyor mu

## 12. Mikro Phase 2 queue ve scheduler notlari

Supplier bazli Mikro siparis sync akisi queue uzerinden calisir.

- Scheduler yalniz `SyncAllActiveSuppliersJob` isini tetikler.
- Calisma araligi `MIKRO_SYNC_INTERVAL` uzerinden belirlenir.
- Admin supplier detay ekranindaki "Simdi Senkronla" butonu tek supplier icin queue isi olusturur.
- Queue worker calismiyorsa manuel sync butonu senkronu tamamlamaz; yalniz kuyruga is birakir.

Tercih edilen ERP-side VIEW kontrati:

- `order_no`
- `line_no`
- `stock_code`
- `stock_name`
- `order_qty`
- `supplier_code`
- `supplier_name`
- `order_date`

Kimlik kurallari:

- `line_no` gercek ERP satir kimligi `sip_satirno` olmalidir.
- Supplier eslestirmesi `supplier_mikro_accounts.mikro_cari_kod = supplier_code` ile yapilir.
- `supplier_name` yalniz gosterim amaclidir.

Docker local queue worker ornegi:

```bash
docker compose exec app php artisan queue:work --queue=default --sleep=1 --tries=3
```

DigitalOcean / production cron ornegi:

```bash
* * * * * cd /var/www/artwork-portal && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

Mikro supplier sync manuel dogrulama:

1. Admin panelde tedarikci detayina gidin.
2. "Simdi Senkronla" butonuna basin.
3. Queue worker logunda job'in calistigini dogrulayin.
4. Supplier ekraninda son sync zamani, durum ve hata ozetinin guncellendigini kontrol edin.

## 13. Exchange / SMTP mail notlari

Bu repo Exchange icin Laravel'in SMTP uyumlu mail katmanini kullanir. Native EWS veya OAuth implementation bu pass kapsaminda yoktur.

Gerekli bootstrap env alanlari:

```env
MAIL_MAILER=smtp
MAIL_HOST=exchange.ornek.local
MAIL_PORT=587
MAIL_USERNAME=portal@firma.com
MAIL_PASSWORD=super-secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=portal@firma.com
MAIL_FROM_NAME="Lider Portal"
```

Runtime admin ayarlari:

- yeni siparis bildirimleri etkin/pasif
- Grafik Departmani alicilari
- CC / BCC listeleri
- yeni siparis konu sablonu
- override from name / address
- test mail alicisi

Davranis notlari:

- Otomatik bildirim yalniz Mikro ile ilk kez olusan sipariste gonderilir.
- Mail disabled veya SMTP erisilemez olsa bile siparis sync akisi bozulmaz.
- Test mail ve otomatik bildirimler queue worker gerektirir.
- MAIL_PASSWORD gibi secret alanlar admin paneline geri basilmadan env'de tutulur.
