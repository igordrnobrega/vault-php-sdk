<?php
namespace IGN\Vault;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;

class ServiceFactory
{
    protected static $services = [
        'sys' => 'IGN\Vault\Services\Sys',
        'data' => 'IGN\Vault\Services\Data',
        'auth/token' => 'IGN\Vault\Services\Auth\Token',
        'auth/approle'=>'IGN\Vault\Services\Auth\AppRole',
        'transit' => 'IGN\Vault\Services\Transit',
    ];

    protected $client;

    public function __construct(array $options = array(), LoggerInterface $logger = null, GuzzleClient $guzzleClient = null)
    {
        $this->client = new Client($options, $logger, $guzzleClient);
    }

    public function get($service)
    {
        if (!array_key_exists($service, self::$services)) {
            throw new \InvalidArgumentException(sprintf('The service "%s" is not available. Pick one among "%s".', $service, implode('", "', array_keys(self::$services))));
        }

        $class = self::$services[$service];

        return new $class($this->client);
    }
}
