.PHONY: up down build restart logs shell migrate fresh seed install clear cache setup test test-unit test-feature erp-sync

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose down && docker compose up -d

logs:
	docker compose logs -f app nginx queue

shell:
	docker compose exec app bash

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

install:
	docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader

clear:
	docker compose exec app php artisan optimize:clear

cache:
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

test:
	docker compose exec app php artisan test

test-unit:
	docker compose exec app php artisan test --testsuite=Unit

test-feature:
	docker compose exec app php artisan test --testsuite=Feature

test-coverage:
	docker compose exec app php artisan test --coverage

erp-sync:
	docker compose exec app php artisan erp:sync

erp-sync-dry:
	docker compose exec app php artisan erp:sync --dry-run

queue-work:
	docker compose exec app php artisan queue:work --verbose

setup:
	cp .env.example .env
	docker compose build
	docker compose up -d
	sleep 8
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --seed
	@echo ""
	@echo "✓ Kurulum tamamlandı!"
	@echo "✓ http://localhost adresinden erişebilirsiniz."
	@echo "✓ Setup wizard: http://localhost/setup"
	@echo "✓ admin@portal.local / Admin1234!"
