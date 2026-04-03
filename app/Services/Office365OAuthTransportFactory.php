<?php

namespace App\Services;

use App\Mail\Transport\Office365OAuthTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Office365OAuthTransportFactory
{
    public function __construct(private Office365AccessTokenProvider $tokenProvider) {}

    public function make(array $config): TransportInterface
    {
        return new Office365OAuthTransport($this->tokenProvider, $config);
    }
}
