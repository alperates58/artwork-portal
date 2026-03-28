# Lider Portal — Sunucu Güncelleme Kılavuzu

GitHub'a yeni commit push ettikten sonra sunucuyu güncellemek için aşağıdaki adımları sırayla uygulayın.

---

## Hızlı Güncelleme (Kod değişikliği, migration yok)

```bash
cd /var/www/artwork-portal
git pull origin main
docker compose exec app php artisan portal:update
```

---

## Tam Güncelleme (Migration, yeni paket veya asset değişikliği varsa)

```bash
cd /var/www/artwork-portal

# 1. Kodu çek
git pull origin main

# 2. Yeni PHP paketi eklendiyse
docker compose exec app composer install --no-dev --optimize-autoloader

# 3. Yeni frontend değişikliği varsa
docker compose run --rm node npm ci
docker compose run --rm node npm run build

# 4. Migration + cache + route + view güncelle
docker compose exec app php artisan portal:update

# 5. Container'ları yeniden başlat
docker compose up -d --force-recreate app queue scheduler
```

---

## Ne Zaman Hangi Adım?

| Değişiklik türü | Gereken ek adım |
|---|---|
| Sadece PHP/Blade değişikliği | `portal:update` yeterli |
| Yeni migration eklendi | `portal:update` (migrate dahil) |
| composer.json değişti | `composer install` + `portal:update` |
| Tailwind/JS değişti | `npm ci` + `npm run build` + `portal:update` |
| Dockerfile değişti | `docker compose build app` + `up -d --force-recreate` |

---

## Doğrulama

```bash
docker compose ps
docker compose exec app php artisan about
```

`artwork_app`, `artwork_queue`, `artwork_scheduler` → Up olmalı.
`Cache`, `Queue`, `Session` → redis olmalı.
