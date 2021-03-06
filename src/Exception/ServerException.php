<?php

declare(strict_types=1);

namespace IGN\Vault\Exception;

use RuntimeException;

class ServerException extends RuntimeException implements VaultExceptionInterface
{
    public $response;

    public function __construct($message, $code = null, $response = null)
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    public function response()
    {
        return $this->response;
    }
}
