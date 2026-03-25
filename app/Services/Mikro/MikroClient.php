<?php

namespace App\Services\Mikro;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MikroClient
{
    public function __construct(private MikroAuth $auth) {}

    public function isEnabled(): bool
    {
        return $this->auth->isEnabled();
    }

    public function request(string $method, string $path, array $query = [], array $payload = []): Response
    {
        $config = $this->auth->config();

        if (! $config['enabled']) {
            throw new MikroException('Mikro entegrasyonu devre disi.');
        }

        if (blank($config['base_url'])) {
            throw new MikroException('Mikro base URL tanimli degil.');
        }

        $url = $this->makeUrl($path);
        $request = $this->pendingRequest($config);
        $context = $this->logContext($method, $path, $query);

        try {
            $response = match (strtoupper($method)) {
                'POST' => $request->post($url, $payload),
                'PUT' => $request->put($url, $payload),
                'PATCH' => $request->patch($url, $payload),
                'DELETE' => $request->delete($url, $payload),
                default => $request->get($url, $query),
            };

            if ($response->failed()) {
                Log::warning('Mikro request failed', $context + ['status' => $response->status()]);

                throw new MikroException(
                    $response->status() === 401 || $response->status() === 403
                        ? 'Mikro kimlik dogrulamasi basarisiz.'
                        : 'Mikro istegi basarisiz oldu.'
                );
            }

            Log::info('Mikro request succeeded', $context + ['status' => $response->status()]);

            return $response;
        } catch (RequestException $exception) {
            Log::warning('Mikro request exception', $context + ['error' => $exception->getMessage()]);

            throw new MikroException('Mikro servisine baglanilamadi.', previous: $exception);
        } catch (\Throwable $exception) {
            if ($exception instanceof MikroException) {
                throw $exception;
            }

            Log::warning('Mikro unexpected exception', $context + ['error' => $exception->getMessage()]);

            throw new MikroException('Mikro istegi tamamlanamadi.', previous: $exception);
        }
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->request('GET', $path, $query);
    }

    public function post(string $path, array $payload = []): Response
    {
        return $this->request('POST', $path, payload: $payload);
    }

    public function testConnection(): bool
    {
        // TODO: Mikro Desktop API auth endpoint dokumani netlestiginde bu probe,
        // dogrudan login/token endpoint'ine tasinmali. Simdilik tek noktadan
        // temel erisim + auth header kontrolu yapiyoruz.
        $this->get($this->connectivityProbePath());

        return true;
    }

    private function pendingRequest(array $config): PendingRequest
    {
        $request = Http::acceptJson()
            ->withHeaders($this->auth->headers())
            ->timeout(max(1, $config['timeout']))
            ->retry(2, 500, throw: false)
            ->withOptions([
                'verify' => $config['verify_ssl'],
            ]);

        $basicAuth = $this->auth->basicAuth();

        if ($basicAuth !== null) {
            [$username, $password] = $basicAuth;
            $request = $request->withBasicAuth($username, $password);
        }

        return $request;
    }

    private function makeUrl(string $path): string
    {
        return $this->auth->config()['base_url'] . '/' . ltrim($path, '/');
    }

    private function connectivityProbePath(): string
    {
        return '/';
    }

    private function logContext(string $method, string $path, array $query = []): array
    {
        return [
            'method' => strtoupper($method),
            'path' => '/' . ltrim($path, '/'),
            'query' => $query,
            'mikro' => $this->auth->maskedConfig(),
        ];
    }
}
