<?php

namespace App\Services\Mikro;

use App\Services\PortalSettings;

class MikroAuth
{
    public function __construct(private PortalSettings $settings) {}

    public function config(): array
    {
        $runtime = $this->settings->mikroConfig();

        return [
            'enabled' => (bool) ($runtime['enabled'] ?? config('mikro.enabled')),
            'base_url' => rtrim((string) ($runtime['base_url'] ?: config('mikro.base_url', '')), '/'),
            'api_key' => (string) ($runtime['api_key'] ?: config('mikro.api_key', '')),
            'username' => (string) ($runtime['username'] ?: config('mikro.username', '')),
            'password' => (string) ($runtime['password'] ?: config('mikro.password', '')),
            'company_code' => (string) ($runtime['company_code'] ?: config('mikro.company_code', '')),
            'work_year' => (string) ($runtime['work_year'] ?: config('mikro.work_year', '')),
            'timeout' => (int) ($runtime['timeout'] ?: config('mikro.timeout', 30)),
            'verify_ssl' => (bool) ($runtime['verify_ssl'] ?? config('mikro.verify_ssl', true)),
        ];
    }

    public function isEnabled(): bool
    {
        return $this->config()['enabled'];
    }

    public function hasConnectionDetails(): bool
    {
        $config = $this->config();

        return filled($config['base_url']);
    }

    public function headers(): array
    {
        $config = $this->config();
        $headers = [
            'Accept' => 'application/json',
        ];

        if (filled($config['api_key'])) {
            $headers['X-API-Key'] = $config['api_key'];
        }

        if (filled($config['company_code'])) {
            $headers['X-Mikro-Company-Code'] = $config['company_code'];
        }

        if (filled($config['work_year'])) {
            $headers['X-Mikro-Work-Year'] = $config['work_year'];
        }

        return $headers;
    }

    public function basicAuth(): ?array
    {
        $config = $this->config();

        if (blank($config['username']) || blank($config['password'])) {
            return null;
        }

        return [$config['username'], $config['password']];
    }

    public function maskedConfig(): array
    {
        $config = $this->config();

        return [
            'enabled' => $config['enabled'],
            'base_url' => $config['base_url'],
            'company_code' => $config['company_code'],
            'work_year' => $config['work_year'],
            'timeout' => $config['timeout'],
            'verify_ssl' => $config['verify_ssl'],
            'has_api_key' => filled($config['api_key']),
            'has_username' => filled($config['username']),
            'has_password' => filled($config['password']),
        ];
    }
}
