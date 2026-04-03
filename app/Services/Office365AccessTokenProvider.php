<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Office365AccessTokenProvider
{
    public function getAccessToken(array $config): string
    {
        $tenantId = trim((string) ($config['tenant_id'] ?? $config['oauth_tenant_id'] ?? ''));
        $clientId = trim((string) ($config['client_id'] ?? $config['oauth_client_id'] ?? ''));
        $clientSecret = (string) ($config['client_secret'] ?? $config['oauth_client_secret'] ?? '');

        if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Office 365 Modern Auth ayarları eksik.');
        }

        $cacheKey = 'office365-mail-access-token:' . sha1($tenantId . '|' . $clientId);
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout((int) ($config['timeout'] ?? 30))
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://outlook.office365.com/.default',
            ]);

        if ($response->failed()) {
            $message = trim((string) ($response->json('error_description') ?? $response->json('error') ?? 'Bilinmeyen hata.'));

            throw new RuntimeException('Office 365 erişim belirteci alınamadı: ' . $message);
        }

        $accessToken = (string) $response->json('access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('Office 365 erişim belirteci yanıtı boş döndü.');
        }

        $expiresIn = max(60, (int) $response->json('expires_in', 3600) - 300);
        Cache::put($cacheKey, $accessToken, now()->addSeconds($expiresIn));

        return $accessToken;
    }
}
