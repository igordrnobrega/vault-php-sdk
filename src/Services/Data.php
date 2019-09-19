<?php
namespace IGN\Vault\Services;

use IGN\Vault\Client;
use IGN\Vault\OptionsResolver;

/**
 * This service class handle data read/write
 *
 */
class Data
{
    /**
     * Client instance
     *
     * @var Client
     */
    private $client;

    /**
     * Path in Vault
     *
     * @var string
     */
    private static $secretPath;

    /**
     * Create a new Data service with an optional Client
     *
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function write($path, $body)
    {
        $params = [
            'body' => json_encode($body)
        ];

        return $this->client->put($this->getSecretPath() . $path, $params);
    }

    public function get($path)
    {
        return $this->client->get($this->getSecretPath() . $path);
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

    public static function setSecretPath(string $secretPath)
    {
        self::$secretPath = $secretPath;
    }
}
