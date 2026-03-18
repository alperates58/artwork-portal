# Artwork Portal — Geliştirici Komutları
.PHONY: up down build fresh logs shell migrate seed

## Containerları başlat
up:
	docker compose up -d

## Containerları durdur
down:
	docker compose down

## Yeniden build et
build:
	docker compose build --no-cache

## Tüm containerları durdur ve yeniden başlat
restart:
	docker compose down && docker compose up -d

## Logları izle
logs:
	docker compose logs -f app nginx

## Laravel migrate
migrate:
	docker compose exec app php artisan migrate

## Temiz kurulum (migrate:fresh + seed)
fresh:
	docker compose exec app php artisan migrate:fresh --seed

## Seed çalıştır
seed:
	docker compose exec app php artisan db:seed

## App container shell
shell:
	docker compose exec app bash

## Composer install
install:
	docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader

## Laravel cache temizle
clear:
	docker compose exec app php artisan optimize:clear

## Production cache
cache:
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

## İlk kurulum (clone sonrası çalıştır)
setup:
	cp .env.example .env
	docker compose build
	docker compose up -d
	sleep 5
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --seed
	@echo ""
	@echo "✓ Kurulum tamamlandı!"
	@echo "✓ http://localhost adresinden erişebilirsiniz."
	@echo "✓ admin@portal.local / Admin1234!"
