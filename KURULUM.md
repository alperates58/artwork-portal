# Lider Portal Kurulum ve Guncelleme

Bu dokuman mevcut Laravel 11 + Docker tabanli Lider Portal reposunun mevcut durumuna gore hazirlandi. Proje greenfield degildir; setup wizard, admin paneli, supplier portal, Redis, queue, scheduler, artwork/revision akislar ve Spaces destegi zaten vardir.

## 1. Lokal Docker kurulumu

```powershell
git clone <repo-url> artwork-portal
cd artwork-portal
Copy-Item .env.example .env
powershell -ExecutionPolicy Bypass -File .\scripts\setup.ps1
```

Sik kullanilan komutlar:

```powershell
.\scripts\dev.ps1 up
.\scripts\dev.ps1 logs
.\scripts\dev.ps1 shell
.\scripts\dev.ps1 migrate
.\scripts\dev.ps1 clear
```

Ilk acilis:

- Uygulama: `http://localhost`
- Setup wizard gerekiyorsa: `http://localhost/setup`

Lokal `.env` notlari:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `FILESYSTEM_DISK=local`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`
- `APP_SLOW_REQUEST_THRESHOLD_MS=800` yalnizca analiz gerektiginde acilmalidir

## 2. Production Droplet kurulumu

Sunucu hazirligi:

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

Production `.env` icin kritik alanlar:

- `APP_NAME="Lider Portal"`
- `APP_URL=https://portal.ornekdomain.com`
- `APP_INSTALLED=true`
- MySQL ve Redis sifreleri
- `FILESYSTEM_DISK=spaces`
- tum `DO_SPACES_*` alanlari
- gerekiyorsa `MIKRO_*` alanlari
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=0`
- `APP_SLOW_REQUEST_THRESHOLD_MS=800`

Container baslatma:

```bash
docker compose build app mysql
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan portal:update
```

Notlar:

- Ilk kurulum wizard ile yapilacaksa gecici olarak `APP_INSTALLED=false` kullanilabilir.
- Mevcut sistem tasiniyorsa hazir `.env` + `portal:update` akisi daha kontrolludur.

## 3. Runtime ayarlar

`.env` icinde kalmasi gerekenler:

- `APP_KEY`
- container, DB ve Redis host bilgileri
- ilk bootstrap icin gerekli varsayilanlar

Admin panelinden yonetilebilen runtime ayarlar:

- Spaces disk secimi ve baglanti alanlari
- Mikro enabled, base URL, API key/kullanici bilgileri, sirket kodu, calisma yili, timeout, SSL dogrulamasi, sevk endpoint yolu ve sync araligi

Bu runtime ayarlar `system_settings` tablosunda tutulur ve calisma zamaninda `.env` degerlerinin uzerine yazabilir. Ilk ayaga kalkis icin `.env` yine gereklidir.

## 4. Guncelleme akisi

GitHub'dan yeni commit geldikten sonra tam kurulum tekrar calistirilmaz.

Temel komut:

```bash
docker compose exec app php artisan portal:update
```

`portal:update` su adimlari uygular:

- `migrate --force`
- `storage:link`
- `optimize:clear`
- `queue:restart`
- production ortaminda `config:cache`, `route:cache`, `view:cache`

Bu sayede veri korunur, yeni migration'lar uygulanir ve portal yeniden kurulum istemeden guncellenir.

## 5. Admin Update Foundation

Bu pass ile admin update alani guvenli bir temel kazandi. Admin artik su bilgileri gorebilir:

- kurulu commit
- aktif branch
- `APP_VERSION` varsa uygulama versiyonu
- son basarili `portal:update` kaydi
- son GitHub kontrol sonucu
- son kontrol zamani
- kisa update gecmisi

GitHub kontrol komutu:

```bash
docker compose exec app php artisan portal:update:check
```

Opsiyonel `.env` alanlari:

- `APP_VERSION`
- `GITHUB_UPDATE_REPOSITORY`
- `GITHUB_UPDATE_BRANCH`
- `GITHUB_UPDATE_TOKEN`

Davranis notlari:

- Public repo icin token zorunlu degildir.
- Sik kontrol yapilacaksa GitHub API rate limit icin token tanimlamak daha sagliklidir.
- Branch once yerel git bilgisinden okunur; okunamazsa `GITHUB_UPDATE_BRANCH` kullanilir.
- GitHub gecici olarak erisilemezse panel bozulmaz; hata kaydi tutulur.

## 6. Guvenli update sinirlari

Admin paneli su anda yalnizca backend uzerinden GitHub kontrolu yapar. Web isteginden su adimlar bilerek calistirilmaz:

- `git pull`
- `composer install`
- `migrate --force`
- servis restart
- rollback

Onerilen production update sirasi:

```bash
cd /var/www/artwork-portal
git fetch --all --prune
git status
git pull origin main
docker compose build app
docker compose up -d
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan portal:update
```

Tek tik deploy ancak su kosullar netlestiginde dusunulmelidir:

- dogru deploy kullanicisi
- yazma izinleri
- repo clone yapisi
- supervisor/systemd/docker operasyon sahipligi
- bakim penceresi ve hata geri donus proseduru

## 7. Rollback gercegi

Rollback ancak su stratejilerden biri varsa makul sekilde guvenli olabilir:

- release klasorleri + symlink gecisi
- Docker image tag ile geri donus
- snapshot / yedek + bakim penceresi

Tek basina `git checkout <old-commit>` guvenli rollback sayilmaz. Cunku:

- migration sonrasi veri geriye uyumlu olmayabilir
- queue isleri eski ve yeni kodla karisik calisabilir
- Spaces uzerindeki dosya degisiklikleri geri alinmayabilir

Bu nedenle admin panelinde rollback butonu yoktur.

## 8. Queue, scheduler ve cache

Production sonrasi kontrol:

```bash
docker compose ps
docker compose logs -f app nginx queue scheduler
docker compose exec app php artisan about
```

Beklenen durum:

- cache `redis`
- queue `redis`
- session `redis`
- `queue` servisi calisiyor
- `scheduler` servisi calisiyor

## 9. Spaces ve guvenli dosya akisi

- `FILESYSTEM_DISK=spaces` olmadan Spaces aktif olmaz
- tum `DO_SPACES_*` alanlari dolu olmalidir
- dosyalar public acilmaz
- indirme akisi guvenli endpoint + presigned URL uzerinden calisir

## 10. Operasyonel notlar

Turkce karakter problemi gorunurse:

- kaynak dosyalarin UTF-8 kaydedildigini dogrulayin
- container yeniden build edin
- tarayici cache temizleyin

Admin ekranlari yavas ise:

- Windows Docker bind mount gecikmesi hissedilebilir
- `APP_SLOW_REQUEST_THRESHOLD_MS=800` ile log toplayin
- `docker compose logs -f mysql` ile slow query logunu izleyin

Frontend ilk yukleme gecikmesi:

- proje halen Tailwind Play CDN kullaniyor
- production ilk yuklemede tarayici tarafinda ek gecikme ve dis CDN bagimliligi olabilir

## 11. Son kontrol listesi

1. Login calisiyor mu
2. Admin panel aciliyor mu
3. Supplier portal aciliyor mu
4. Siparis goruntuleme ve duzenleme calisiyor mu
5. Upload/download akisi calisiyor mu
6. Queue job'lari isleniyor mu
7. `portal:update` sonrasi sistem yeniden kurulum istemeden aciliyor mu
