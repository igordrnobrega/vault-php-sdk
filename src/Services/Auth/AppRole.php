<?php

declare(strict_types=1);

namespace IGN\Vault\Services\Auth;

use IGN\Vault\Client;

/**
 * This service class handles Vault HTTP API endpoints starting in /auth/approle/.
 */
class AppRole
{
    /**
     * Client instance.
     *
     * @var Client
     */
    private $client;

    /**
     * Constructor - initialize Client field.
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     *  Issues a Vault token based on the presented credentials.
     *
     * @param string $roleId The role_id in Vault
     * @param string $secretId The secret_id in Vault
     */
    public function login(string $roleId, string $secretId)
    {
        $body = ['role_id' => $roleId, 'secret_id' => $secretId];
        $params = [
            'body' => json_encode($body),
        ];

        return \GuzzleHttp\json_decode($this->client->post('/auth/approle/login', $params)->getBody());
    }

    /**
     * List the AppRoles defined in Vault.
     */
    public function listRoles()
    {
        return \GuzzleHttp\json_decode($this->client->list('/auth/approle/role')->getBody());
    }

    /**
     * Get the ID for the specified AppRole.
     */
    public function getRoleId(string $roleName)
    {
        return \GuzzleHttp\json_decode($this->client->get("/auth/approle/role/$roleName/role-id")->getBody());
    }
}
