<?php

namespace App\SearchEngine;

use Typesense\Client;
use Typesense\Collections;
use Typesense\Debug;
use Typesense\Exceptions\ConfigError;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TypeSenseClient
{
    private Client $client;

    /**
     * @throws ConfigError
     */
    public function __construct(
        #[Autowire('%env(TYPESENSE_API_KEY)%')]
        private readonly string $typesenseApiKey,
        #[Autowire('%env(TYPESENSE_HOST)%')]
        private readonly string $typesenseHost,
        #[Autowire('%env(TYPESENSE_PORT)%')]
        private readonly int $typesensePort,
        #[Autowire('%env(TYPESENSE_PROTOCOL)%')]
        private readonly string $typesenseProtocol,
    ) {
        $this->client = new Client([
            'api_key' => $this->typesenseApiKey,
            'nodes' => [
                [
                    'host' => $this->typesenseHost,
                    'port' => $this->typesensePort,
                    'protocol' => $this->typesenseProtocol,
                ],
            ],
            'connection_timeout_seconds' => 2,
        ]);
    }

    public function collections(): Collections
    {
        return $this->client->collections;
    }

    public function debug(): Debug
    {
        return $this->client->debug;
    }
}
