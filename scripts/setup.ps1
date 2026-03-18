param(
  [switch]$Rebuild
)

$ErrorActionPreference = "Stop"

function Import-DotEnv($path) {
  if (-not (Test-Path $path)) { return }
  Get-Content $path | ForEach-Object {
    $line = $_.Trim()
    if ($line.Length -eq 0) { return }
    if ($line.StartsWith("#")) { return }
    $idx = $line.IndexOf("=")
    if ($idx -lt 1) { return }
    $key = $line.Substring(0, $idx).Trim()
    $val = $line.Substring($idx + 1).Trim()
    if (($val.StartsWith('"') -and $val.EndsWith('"')) -or ($val.StartsWith("'") -and $val.EndsWith("'"))) {
      $val = $val.Substring(1, $val.Length - 2)
    }
    if ($key) { Set-Item -Path "Env:$key" -Value $val }
  }
}

function Assert-Command($name) {
  if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
    throw "Komut bulunamadı: $name. Docker Desktop ve 'docker compose' kurulu olmalı."
  }
}

Assert-Command "docker"

Write-Host "==> .env kontrol ediliyor..."
if (-not (Test-Path ".\.env")) {
  Copy-Item ".\.env.example" ".\.env"
  Write-Host "   .env oluşturuldu (.env.example kopyalandı)."
} else {
  Write-Host "   .env zaten var, dokunulmadı."
}

Import-DotEnv ".\.env"

if ($Rebuild) {
  Write-Host "==> Image'lar yeniden build ediliyor..."
  docker compose build --no-cache
} else {
  Write-Host "==> Image'lar build ediliyor..."
  docker compose build
}

Write-Host "==> Container'lar başlatılıyor..."
docker compose up -d

Write-Host "==> MySQL ayağa kalkması bekleniyor..."
$rootPwd = if ($env:DB_ROOT_PASSWORD) { $env:DB_ROOT_PASSWORD } else { "rootsecret" }
for ($i = 0; $i -lt 30; $i++) {
  try {
    docker compose exec -T mysql mysqladmin ping -h localhost -u root "-p$rootPwd" | Out-Null
    Write-Host "   MySQL hazır."
    break
  } catch {
    Start-Sleep -Seconds 2
    if ($i -eq 29) {
      Write-Host "   MySQL hala hazır değil; yine de devam ediyorum (ilk çalıştırmada uzun sürebilir)."
    }
  }
}

Write-Host "==> Composer install..."
docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

Write-Host "==> APP_KEY üretiliyor..."
docker compose exec -T app php artisan key:generate

Write-Host "==> Migration + seed..."
docker compose exec -T app php artisan migrate --seed

Write-Host ""
Write-Host "✓ Kurulum tamamlandı!"
Write-Host "✓ http://localhost adresinden erişebilirsiniz."
Write-Host "✓ Setup wizard: http://localhost/setup"
Write-Host "✓ admin@portal.local / Admin1234!"
