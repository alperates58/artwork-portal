<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

class MailServerConnectionTester
{
    public function test(array $config): void
    {
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
