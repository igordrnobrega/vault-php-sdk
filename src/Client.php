<?php
namespace IGN\Vault;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use http\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use IGN\Vault\Exception\ClientException;
use IGN\Vault\Exception\ServerException;

/**
 * @method Response get($url, array $options)
 * @method Response head($url, array $options)
 * @method Response delete($url, array $options)
 * @method Response put($url, array $options)
 * @method Response patch($url, array $options)
 * @method Response post($url, array $options)
 * @method Response options($url, array $options)
 * @method Response list($url, array $options)
 */
class Client
{
    private const VERSION = '/v1';

    private $client;
    private $logger;

    public function __construct(array $options = [], LoggerInterface $logger = null, GuzzleClient $client = null)
    {
        $base_uri = 'http://127.0.0.1:8200';

        if (isset($options['base_uri'])) {
            $base_uri = $options['base_uri'];
        } else if (getenv('VAULT_ADDR') !== false) {
            $base_uri = getenv('VAULT_ADDR');
        }

        $options = array_replace([
            'base_uri' => $base_uri,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'Vault-PHP-SDK/1.0',
                'Content-Type' => 'application/json',
            ],
        ], $options);

        $this->client = $client ?: new GuzzleClient($options);
        $this->logger = $logger ?: new NullLogger();
    }

    public function __call($name, $arguments)
    {
        $url = $arguments[0];
        $options = $arguments[1] ?? [];

        if (!(is_null($url) || is_string($url) || $url instanceof Uri)) {
            throw new \InvalidArgumentException('First argument must be "null|string|Uri"');
        }

        if (!is_array($options)) {
            throw new \InvalidArgumentException('Second argument must be "array"');
        }

        return $this->send(new Request($name, $this->getUrl($url)), $options);
    }

    public function send(RequestInterface $request, $options = []): Response
    {
        $this->logger->info(sprintf('%s "%s"', $request->getMethod(), $request->getUri()));
        $this->logger->debug(sprintf("Request:\n%s\n%s\n%s", $request->getUri(), $request->getMethod(), json_encode($request->getHeaders())));

        try {
            $response = $this->client->send($request, $options);
        } catch (TransferException $e) {
            $this->handleException($e);
        }

        $this->handleResponseErrors($response);

        return $response;
    }

    private function handleResponseErrors(Response $response)
    {
        if (400 <= $response->getStatusCode()) {
            $message = sprintf('Something went wrong when calling vault (%s - %s).', $response->getStatusCode(), $response->getReasonPhrase());

            $this->logger->error($message);
            $this->logger->debug(sprintf("Response:\n%s\n%s\n%s", $response->getStatusCode(), json_encode($response->getHeaders()), $response->getBody()->getContents()));

            $message .= "\n" . (string) $response->getBody();
            if (500 <= $response->getStatusCode()) {
                throw new ServerException($message, $response->getStatusCode(), $response);
            }

            throw new ClientException($message, $response->getStatusCode(), $response);
        }
    }

    private function handleException(\Exception $exception)
    {
        $message = sprintf('Something went wrong when calling vault (%s).', $e->getMessage());
        $this->logger->error($message);

        throw new ServerException($message);
    }

    private function getUrl($url = null)
    {
        if ($url instanceof Uri) {
            return $url;
        }

        return is_null($url) ?: self::VERSION . $url;
    }
}
