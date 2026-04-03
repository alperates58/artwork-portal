<?php

namespace App\Mail\Transport;

use App\Services\Office365AccessTokenProvider;
use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\RawMessage;

class Office365OAuthTransport extends EsmtpTransport
{
    private string $sender;

    public function __construct(
        private Office365AccessTokenProvider $tokenProvider,
        private readonly array $config
    ) {
        parent::__construct(
            (string) ($config['host'] ?? 'smtp.office365.com'),
            (int) ($config['port'] ?? 587),
            null
        );

        $this->sender = trim((string) ($config['sender'] ?? $config['oauth_sender'] ?? $config['from_address'] ?? ''));

        if ($this->sender === '') {
            throw new RuntimeException('Office 365 gönderici mailbox alanı zorunludur.');
        }

        $this->setAuthenticators([new XOAuth2Authenticator()]);
        $this->setAutoTls(true);
        $this->setRequireTls(true);

        if (filled($config['local_domain'] ?? null)) {
            $this->setLocalDomain((string) $config['local_domain']);
        }

        $stream = $this->getStream();

        if ($stream instanceof SocketStream && isset($config['timeout'])) {
            $stream->setTimeout((int) $config['timeout']);
        }
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $this->stop();
        $this->setUsername($this->sender);
        $this->setPassword($this->tokenProvider->getAccessToken($this->config));

        return parent::send($message, $envelope);
    }
}
