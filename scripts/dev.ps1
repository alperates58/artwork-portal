param(
  [ValidateSet("up","down","restart","logs","shell","migrate","fresh","test","clear","cache")]
  [string]$Cmd = "up"
)

$ErrorActionPreference = "Stop"

switch ($Cmd) {
  "up"       { docker compose up -d }
  "down"     { docker compose down }
  "restart"  { docker compose down; docker compose up -d }
  "logs"     { docker compose logs -f app nginx queue }
  "shell"    { docker compose exec app bash }
  "migrate"  { docker compose exec app php artisan migrate }
  "fresh"    { docker compose exec app php artisan migrate:fresh --seed }
  "test"     { docker compose exec app php artisan test }
  "clear"    { docker compose exec app php artisan optimize:clear }
  "cache"    {
    docker compose exec app php artisan config:cache
    docker compose exec app php artisan route:cache
    docker compose exec app php artisan view:cache
  }
}
