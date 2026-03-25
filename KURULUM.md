# Lider Portal Kurulum ve Güncelleme

Bu doküman mevcut Laravel 11 + Docker tabanlı Lider Portal reposu için hazırlanmıştır. Proje greenfield değildir; setup wizard, admin paneli, supplier portal, artwork/revision akışları, Redis, queue, scheduler ve Spaces desteği zaten vardır. Amaç mevcut çalışan davranışı koruyarak production kurulumu ve güncelleme sürecini güvenli hale getirmektir.

## 1. Mimari notlar

- Veritabanı oluşturma bugün altyapı seviyesindedir.
- Lokal Docker akışında MySQL container veritabanını hazır getirir.
- Setup wizard uygulama içi kurulum, admin kullanıcı ve bağlantı doğrulama rolünü sürdürür.
- Bu pass içinde setup wizard veritabanı oluşturan bir araca dönüştürülmemiştir.
- İleride istenirse “DB oluşturma uygulama sorumluluğu mu, altyapı sorumluluğu mu?” kararı ayrı mimari iş olarak netleştirilmelidir.

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
.\scripts\dev.ps1 clear
```

İlk açılış:

- Uygulama: `http://localhost`
- Setup wizard gerekiyorsa: `http://localhost/setup`

Lokal `.env` notları:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_VERSION=1.2.0`
- `APP_TIMEZONE=Europe/Istanbul`
- `FILESYSTEM_DISK=local`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`
- `APP_SLOW_REQUEST_THRESHOLD_MS=800` yalnız analiz gerektiğinde açılmalıdır

## 3. DigitalOcean Droplet hazırlığı

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

## 4. Production `.env` zorunlu alanları

En az şu alanları doldurun:

- `APP_NAME="Lider Portal"`
- `APP_URL=https://portal.ornekdomain.com`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_VERSION=1.2.0`
- `APP_TIMEZONE=Europe/Istanbul`
- `APP_INSTALLED=true` veya ilk kurulum wizard kullanılacaksa geçici olarak `false`
- `DB_*` alanları
- `REDIS_*` alanları
- `FILESYSTEM_DISK=spaces`
- tüm `DO_SPACES_*` alanları
- gerekiyorsa `MIKRO_*` alanları
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=0`

## 5. Redis kurulumu ve beklenti

Production için Redis öneri değil, fiilen beklenen çalışma şeklidir. Cache, queue ve session sürücüsü Redis olacak şekilde ayarlıdır.

Bu repo Docker Compose içinde Redis servisiyle gelir. Production’da ayrıca sistem seviyesinde Redis kurmanız gerekmez; compose servisi ayakta olduğu sürece uygulama bunu kullanır.

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

Redis yoksa:

- `SESSION_DRIVER=file` gibi sessiz fallback yapmayın
- önce Redis servisini ayağa kaldırın
- sonra `php artisan optimize:clear` çalıştırın

## 6. Spaces yapılandırması

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

- Bucket private kalmalıdır
- İndirme akışı public URL üzerinden değil, yetkili endpoint + presigned URL ile çalışır
- `FILESYSTEM_DISK=spaces` olmadan yalnız Spaces bilgisi girmek yeterli değildir

## 7. Container build ve ilk ayağa kaldırma

```bash
cd /var/www/artwork-portal
docker compose build app mysql
docker compose up -d --force-recreate app mysql redis queue scheduler nginx
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

## 8. Frontend asset build

Bu repo artık production dostu yerel asset hattı için `Vite + Tailwind` yapılandırması içerir.

Production deploy sırasında şu adım zorunludur:

```bash
npm install
npm run build
```

Notlar:

- Build çıktısı `public/build` altına yazılır
- Build varsa uygulama yerel asset kullanır
- Build yoksa yalnız geçici fallback olarak eski CDN yolu devreye girer
- Production’da fallback’e güvenmeyin; mutlaka build alın

## 9. Güvenli update akışı

Admin paneli artık şunları gösterir:

- kurulu sürüm
- hedef sürüm
- release özeti
- değişiklik maddeleri
- etkilenen modüller
- migration/schema görünürlüğü
- uyarılar
- post-update notları
- zengin update history

GitHub kontrol komutu:

```bash
docker compose exec app php artisan portal:update:check
```

Uygulama update komutu:

```bash
docker compose exec app php artisan portal:update
```

`portal:update` şu adımları uygular:

- `migrate --force`
- `storage:link`
- `optimize:clear`
- `queue:restart`
- production ortamında `config:cache`, `route:cache`, `view:cache`

Admin panelindeki “Hazırlığı Onayla” işlemi:

- deploy çalıştırmaz
- rollback yapmaz
- yalnız hedef release için onaylı hazırlık kaydı oluşturur

Önerilen production update sırası:

```bash
cd /var/www/artwork-portal
git fetch --all --prune
git status
git pull origin main
docker compose build app
docker compose up -d --force-recreate app queue scheduler nginx
docker compose exec app composer install --no-dev --optimize-autoloader
npm install
npm run build
docker compose exec app php artisan portal:update
```

## 10. Rollback gerçeği

Güvenli rollback için şu stratejilerden biri gerekir:

- release klasörleri + symlink geçişi
- Docker image tag ile geri dönüş
- snapshot / yedek + bakım penceresi

Şunlar güvenli rollback sayılmaz:

- tek başına `git checkout <old-commit>`
- migration sonrası şemasal uyumsuzluğu hesaba katmayan geri dönüş
- queue ve scheduler karışık kod sürümleriyle çalışırken yapılan acele geri dönüş

Bu nedenle admin panelinde rollback butonu yoktur.

## 11. Timezone ve UTF-8 disiplini

Bu repo için standart:

- timezone: `Europe/Istanbul`
- locale: `tr`
- dosya kodlaması: `UTF-8`

Kontrol noktaları:

- update geçmişi saatleri Istanbul zamanına göre görünmelidir
- yeni release notları ve changelog Türkçe karakterleri bozmadan görünmelidir
- mojibake görülürse ilgili dosya UTF-8 olarak yeniden kaydedilmelidir

## 12. Son doğrulama listesi

1. Login çalışıyor mu
2. Admin panel açılıyor mu
3. Supplier portal açılıyor mu
4. Sipariş ve artwork akışları bozulmadı mı
5. Upload/download akışı çalışıyor mu
6. Redis, queue ve scheduler ayakta mı
7. `portal:update` sonrası sistem yeniden kurulum istemeden açılıyor mu
8. Admin ayarlar ekranında release notları ve history görünüyor mu
9. `npm run build` sonrası layout bozulmadan açılıyor mu
