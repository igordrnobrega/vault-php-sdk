<?php

declare(strict_types=1);

namespace IGN\Vault\Services;

use IGN\Vault\Client;

/**
 * This service class handle data read/write.
 */
class Data
{
    /**
     * Specifies the version to return. If not set the latest version is returned.
     */
    const LATEST_VERSION = '0';

    /**
     * Client instance.
     *
     * @var Client
     */
    private $client;

    /**
     * Path in Vault.
     *
     * @var string
     */
    private static $secretPath;

    /**
     * Create a new Data service with an optional Client.
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function write($path, $body)
    {
        $params = [
            'body' => json_encode($body),
        ];

        return $this->client->put($this->getSecretPath() . $path, $params);
    }

    public function get($path, string $version = self::LATEST_VERSION)
    {
        $options = [
            'query' => [
                'version' => $version,
            ],
        ];

        return $this->client->get($this->getSecretPath() . $path, $options);
    }

    public function delete($path)
    {
        return $this->client->delete($this->getSecretPath() . $path);
    }

    public function list($path)
    {
        return $this->client->list($this->getSecretPath() . $path);
    }

    public function getSecretPath()
    {
        return '/' . self::$secretPath . '/';
    }

    public static function setSecretPath(string $secretPath): void
    {
        self::$secretPath = $secretPath;
    }
}
