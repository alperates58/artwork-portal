# Lider Portal — Sunucu Güncelleme Kılavuzu

GitHub'a yeni commit push ettikten sonra sunucuyu güncellemek için aşağıdaki adımları sırayla uygulayın.

---

## Hızlı Güncelleme (Kod değişikliği, migration yok)

```bash
cd /var/www/artwork-portal
git pull origin main
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan portal:update
```

> **Önemli:** `portal:update` öncesi her zaman `config:clear` ve `cache:clear` çalıştırın.
> Aksi halde config cache'de eski değerler kalır ve uygulama setup wizard'a yönlendirebilir.
>
> **Not:** Admin paneldeki "GitHub'dan Güncelle" butonu artık frontend asset build adımını da otomatik dener (`npm ci` + `npm run build`).
> Bu adımın çalışması için app image'ın güncel olması gerekir.

---

## Tam Güncelleme (Migration, yeni paket veya asset değişikliği varsa)

```bash
cd /var/www/artwork-portal
git config --global --add safe.directory /var/www/artwork-portal

# 1. Kodu çek
git pull origin main

# 2. Cache'i temizle (her zaman zorunlu)
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear

# 3. Yeni PHP paketi eklendiyse
docker compose exec app composer install --no-dev --optimize-autoloader

# 4. Yeni frontend değişikliği varsa
docker compose exec -u www-data app sh -lc "npm ci --no-audit --no-fund && npm run build"

# 5. Migration + cache + route + view güncelle
docker compose exec app php artisan portal:update

# 6. Container'ları yeniden başlat
docker compose up -d --force-recreate app queue scheduler
```

Eğer update akışında `npm` bulunamadı hatası alırsanız bir defa şu adımları uygulayın:

```bash
cd /var/www/artwork-portal
git pull origin main
docker compose build app queue scheduler
docker compose up -d app queue scheduler
docker compose exec app npm -v
```

---

## Ne Zaman Hangi Adım?

| Değişiklik türü | Gereken ek adım |
|---|---|
| Sadece PHP/Blade değişikliği | `config:clear` + `cache:clear` + `portal:update` |
| Yeni migration eklendi | `config:clear` + `cache:clear` + `portal:update` (migrate dahil) |
| composer.json değişti | `composer install` + `config:clear` + `cache:clear` + `portal:update` |
| Tailwind/JS değişti | `docker compose exec -u www-data app sh -lc "npm ci --no-audit --no-fund && npm run build"` + `config:clear` + `cache:clear` + `portal:update` |
| Dockerfile değişti | `docker compose build app` + `up -d --force-recreate` |

---

## Güncelleme Ekranında `npm EACCES` Hatası

`npm ERR! EACCES ... node_modules` görüyorsanız izinleri bir kez düzeltin:

```bash
cd /var/www/artwork-portal
docker compose exec -u root app sh -lc "mkdir -p /var/www/html/node_modules /var/www/.npm /var/www/.config /var/www/html/public/build && chown -R www-data:www-data /var/www/html/node_modules /var/www/.npm /var/www/.config /var/www/html/public/build"
docker compose exec -u www-data app sh -lc "npm install --no-audit --no-fund && npm run build"
docker compose restart app nginx
```

Sonrasında paneldeki `GitHub'dan Güncelle` butonu izinsiz hata vermeden çalışır.

---

## Güncelleme Sonrası Setup Wizard'a Düşüyorsa

`portal:update` sonrası config cache sıfırlanınca `APP_INSTALLED` değeri kaybolabilir. Aşağıdaki adımları sırayla uygulayın:

```bash
# 1. Cache'i tamamen temizle
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear

# 2. .setup_complete dosyasının varlığını kontrol et
docker compose exec app ls storage/app/.setup_complete

# Dosya yoksa oluştur
docker compose exec app touch storage/app/.setup_complete

# 3. .env dosyasını kontrol et
grep APP_INSTALLED /var/www/artwork-portal/.env
# APP_INSTALLED=true olmalı, değilse düzelt:
sed -i 's/APP_INSTALLED=false/APP_INSTALLED=true/' .env

# 4. Doğrula
docker compose exec app php artisan tinker --execute="echo env('APP_INSTALLED');"
# "1" veya "true" dönmeli

# 5. App'i yeniden başlat
docker compose restart app
```

---

## Doğrulama

```bash
docker compose ps
docker compose exec app php artisan about
```

Beklenen durum:
- `artwork_app`, `artwork_queue`, `artwork_scheduler` → Up
- `Cache`, `Queue`, `Session` → redis
- `Config` → CACHED

---

## Sık Kullanılan Komutlar

```bash
docker compose ps                                    # Container durumu
docker compose logs app --tail=50                   # App logu
docker compose exec app php artisan about           # Uygulama durumu
docker compose exec app php artisan portal:update   # Güncelle
docker compose restart app nginx                    # Yeniden başlat
```
