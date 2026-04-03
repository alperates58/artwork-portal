<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

class MailServerConnectionTester
{
    public function __construct(private Office365OAuthTransportFactory $office365TransportFactory) {}

    public function test(array $config): void
    {
        if (($config['provider'] ?? 'smtp') === 'office365_oauth') {
            $transport = $this->office365TransportFactory->make([
                'host' => $config['host'] ?? 'smtp.office365.com',
                'port' => $config['port'] ?? 587,
                'tenant_id' => $config['oauth_tenant_id'] ?? null,
                'client_id' => $config['oauth_client_id'] ?? null,
                'client_secret' => $config['oauth_client_secret'] ?? null,
                'sender' => $config['oauth_sender'] ?? $config['from_address'] ?? null,
                'from_address' => $config['from_address'] ?? null,
                'timeout' => 30,
                'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
            ]);

            if (! $transport instanceof SmtpTransport) {
                throw new RuntimeException('Office 365 transport olusturulamadi.');
            }

            $transport->start();
            $transport->stop();

            return;
        }

        $host = trim((string) ($config['host'] ?? ''));
        $port = (int) ($config['port'] ?? 0);

        if ($host === '') {
            throw new RuntimeException('Mail host ayari bulunamadi.');
        }

        if ($port < 1) {
            throw new RuntimeException('Mail port ayari gecersiz.');
        }

        $transport = Transport::fromDsn($this->buildDsn($config));

        if (! $transport instanceof SmtpTransport) {
            throw new RuntimeException('SMTP baglantisi icin uygun transport olusturulamadi.');
        }

        $transport->start();
        $transport->stop();
    }

    private function buildDsn(array $config): string
    {
        $scheme = ($config['encryption'] ?? null) === 'ssl' ? 'smtps' : 'smtp';
        $username = trim((string) ($config['username'] ?? ''));
        $password = (string) ($config['password'] ?? '');
        $host = trim((string) ($config['host'] ?? ''));
        $port = (int) ($config['port'] ?? 0);

        $credentials = '';

        if ($username !== '') {
            $credentials = rawurlencode($username);

            if ($password !== '') {
                $credentials .= ':' . rawurlencode($password);
            }

            $credentials .= '@';
        }

        return sprintf('%s://%s%s:%d', $scheme, $credentials, $host, $port);
    }
}
