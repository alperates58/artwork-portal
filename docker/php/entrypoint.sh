#!/bin/sh
set -e

mkdir -p \
  /var/www/html/bootstrap/cache \
  /var/www/html/storage/app \
  /var/www/html/storage/framework/cache/data \
  /var/www/html/storage/framework/sessions \
  /var/www/html/storage/framework/views \
  /var/www/html/storage/logs

chmod -R 0777 /var/www/html/bootstrap/cache /var/www/html/storage || true

exec "$@"
