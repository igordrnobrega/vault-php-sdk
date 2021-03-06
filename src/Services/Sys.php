<?php

declare(strict_types=1);

namespace IGN\Vault\Services;

use IGN\Vault\Client;
use IGN\Vault\OptionsResolver;

/**
 * This service class handle all Vault HTTP API endpoints starting in /sys/.
 */
class Sys
{
    /**
     * Client instance.
     *
     * @var Client
     */
    private $client;

    /**
     * Create a new Sys service with an optional Client.
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     * Return the initialization status of a Vault.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-init.html
     */
    public function status()
    {
        return $this->client->get('/sys/init');
    }

    /**
     * Initializes a new Vault.
     *
     * The Vault must've not been previously initialized
     *
     * @see    https://www.vaultproject.io/docs/http/sys-init.html
     */
    public function init(array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['secret_shares', 'secret_threshold', 'pgp_keys']);
        $body = OptionsResolver::required($body, ['secret_shares', 'secret_threshold']);

        $params = [
            'body' => json_encode($body),
        ];

        return $this->client->put('/sys/init', $params);
    }

    /**
     * Returns the seal status of the Vault.
     *
     * This is an unauthenticated endpoint
     *
     * @see    https://www.vaultproject.io/docs/http/sys-seal-status.html
     */
    public function sealStatus()
    {
        return $this->client->get('/sys/seal-status');
    }

    /**
     * Seals the Vault.
     *
     * In HA mode, only an active node can be sealed.
     *
     * Standby nodes should be restarted to get the same effect.
     *
     * Requires a token with root policy or sudo capability on the path.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-seal.html
     */
    public function seal()
    {
        return $this->client->put('/sys/seal');
    }

    /**
     * Utility method for checking if the vault is sealed or not.
     *
     * @return bool
     */
    public function sealed()
    {
        return json_decode($this->sealStatus()->getBody(), true)['sealed'];
    }

    /**
     * Utility method for checking if the vault is unsealed or not.
     *
     * @return bool
     */
    public function unsealed()
    {
        return !json_decode($this->sealStatus()->getBody(), true)['sealed'];
    }

    /**
     * Enter a single master key share to progress the unsealing of the Vault.
     *
     * If the threshold number of master key shares is reached, Vault will attempt to unseal the Vault.
     *
     * Otherwise, this API must be called multiple times until that threshold is met.
     *
     * Either the key or reset parameter must be provided; if both are provided, reset takes precedence.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-unseal.html
     */
    public function unseal(array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['key', 'reset']);

        $params = [
            'body' => json_encode($body),
        ];

        return $this->client->put('/sys/unseal', $params);
    }

    /**
     * Lists all the mounted secret backends.
     *
     * default_lease_ttl or max_lease_ttl values of 0 mean that the system defaults are used by this backend.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-mounts.html
     */
    public function mounts()
    {
        return $this->client->get('/sys/mounts');
    }

    /**
     * Mount a new secret backend to the mount point in the URL.
     *
     * @param  string $name
     */
    public function createMount($name, array $body)
    {
        $body = OptionsResolver::resolve($body, ['type', 'description', 'config']);

        $params = [
            'body' => json_encode($body),
        ];

        return $this->client->post('/sys/mounts/' . $name, $params);
    }

    /**
     * Unmount the mount point specified in the URL.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-mounts.html
     *
     * @param  string $name
     */
    public function deleteMount($name)
    {
        return $this->client->delete('/sys/mounts/' . $name);
    }

    /**
     * Remount an already-mounted backend to a new mount point.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-remount.html
     *
     * @param  string $from
     * @param  string $to
     */
    public function remount($from, $to)
    {
        $body = compact('from', 'to');

        $params = [
            'body' => json_encode($body),
        ];

        return $this->client->post('/sys/remount', $params);
    }

    /**
     * List or change the given mount's configuration.
     *
     * if `$body` is not empty, a POST to update a mount tune is assumed.
     *
     * Unlike the mounts endpoint, this will return the current time in seconds for each TTL,
     * which may be the system default or a mount-specific value.
     *
     * @param  string $name
     */
    public function tuneMount($name, array $body = [])
    {
        if (empty($body)) {
            return $this->client->get('/sys/mounts/' . $name . '/tune');
        }

        $params = [
            'body' => json_encode(OptionsResolver::resolve($body, ['default_lease_ttl', 'max_lease_ttl'])),
        ];

        return $this->client->post('/sys/mounts/' . $name . '/tune', $params);
    }

    /**
     * Lists all the available policies.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-policy.html
     */
    public function policies()
    {
        return $this->client->get('/sys/policy');
    }

    /**
     * Retrieve the rules for the named policy.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-policy.html
     *
     * @param  string $name
     */
    public function policy($name)
    {
        return $this->client->get('/sys/policy/' . $name);
    }

    /**
     * Add or update a policy.
     *
     * Once a policy is updated, it takes effect immediately to all associated users.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-policy.html
     *
     * @param  string $name
     */
    public function putPolicy($name, array $body)
    {
        $body = OptionsResolver::resolve($body, ['policy']);

        $params = [
            'body' => json_encode($body),
        ];

        return $this->client->put('/sys/policy/' . $name, $params);
    }

    /**
     * Delete the policy with the given name.
     *
     * This will immediately affect all associated users
     *
     * @see    https://www.vaultproject.io/docs/http/sys-policy.html
     *
     * @param  string $name
     */
    public function deletePolicy($name)
    {
        return $this->client->delete('/sys/policy/' . $name);
    }

    /**
     * Returns the capabilities of the token on the given path.
     *
     * If token is empty, 'capabilities-self' is assumed
     *
     * @see    https://www.vaultproject.io/docs/http/sys-capabilities.html
     * @see    https://www.vaultproject.io/docs/http/sys-capabilities-self.html
     *
     * @param  string      $path
     * @param  string|null $token
     */
    public function capabilities($path, $token = null)
    {
        $params = [
            'body' => json_encode(array_filter(compact('token', 'path'))),
        ];

        if (empty($token)) {
            return $this->client->post('/sys/capabilities-self', $params);
        }

        return $this->client->post('/sys/capabilities', $params);
    }

    /**
     * Renew a secret, requesting to extend the lease.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-renew.html
     *
     * @param  string      $leaseId
     * @param  string|null $increment
     */
    public function renew($leaseId, $increment = null)
    {
        $params = [
            'body' => json_encode(array_filter(compact('increment'))),
        ];

        return $this->client->put('/sys/renew/' . $leaseId, $params);
    }

    /**
     * revoke a secret immediately.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-revoke.html
     *
     * @param  string $leaseId
     */
    public function revoke($leaseId)
    {
        return $this->client->put('/sys/revoke/' . $leaseId);
    }

    /**
     * Revoke all secrets generated under a given prefix immediately.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-revoke-prefix.html
     *
     * @param  string $prefix
     */
    public function revokePrefix($prefix)
    {
        return $this->client->put('/sys/revoke-prefix/' . $prefix);
    }

    /**
     * Revoke all secrets generated under a given prefix immediately.
     *
     * Unlike /sys/revoke-prefix, this path ignores backend errors encountered during revocation.
     *
     * This is potentially very dangerous and should only be used in specific emergency situations where errors
     * in the backend or the connected backend service prevent normal revocation.
     *
     * By ignoring these errors, Vault abdicates responsibility for ensuring that the issued credentials
     * or secrets are properly revoked and/or cleaned up.
     *
     * Access to this endpoint should be tightly controlled.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-revoke-force.html
     *
     * @param  string $prefix
     */
    public function revokeForce($prefix)
    {
        return $this->client->put('/sys/revoke-force/' . $prefix);
    }

    /**
     * Returns the high availability status and current leader instance of Vault.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-leader.html
     */
    public function leader()
    {
        return $this->client->get('/sys/leader');
    }

    /**
     * Forces the node to give up active status.
     *
     * If the node does not have active status, this endpoint does nothing.
     *
     * Note that the node will sleep for ten seconds before attempting to grab the active lock again,
     * but if no standby nodes grab the active lock in the interim, the same node may become the active node again.
     *
     * Requires a token with root policy or sudo capability on the path.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-step-down.html
     */
    public function stepDown()
    {
        return $this->client->get('/sys/step-down');
    }

    /**
     * Returns information about the current encryption key used by Vault.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-key-status.html
     */
    public function keyStatus()
    {
        return $this->client->get('/sys/key-status');
    }

    /**
     * Trigger a rotation of the backend encryption key.
     *
     * This is the key that is used to encrypt data written to the storage backend, and is not provided to operators.
     *
     * This operation is done online.
     *
     * Future values are encrypted with the new key, while old values are decrypted with previous encryption keys.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-rotate.html
     */
    public function rotate()
    {
        return $this->client->put('/sys/rotate');
    }

    /**
     * Reads the value of the key at the given path.
     *
     * This is the raw path in the storage backend and not the logical path that is exposed via the mount system.
     *
     * If `$value` is empty, GET is assumed - otherwise PUT.
     *
     * @see    https://www.vaultproject.io/docs/http/sys-raw.html
     *
     * @param  string $path
     * @param  string|null $value
     */
    public function raw($path, $value = null)
    {
        if ($value === null) {
            return $this->client->get('/sys/raw/' . $path);
        }

        $params = [
            'body' => json_encode(compact('value')),
        ];

        return $this->client->put('/sys/raw/' . $path, $params);
    }

    /**
     * Delete the key with given path.
     *
     * This is the raw path in the storage backend and not the logical path that is exposed via the mount system
     *
     * @see    https://www.vaultproject.io/docs/http/sys-raw.html
     *
     * @param  string $path
     */
    public function deleteRaw($path)
    {
        return $this->client->delete('/sys/raw/' . $path);
    }

    /**
     * Returns the health status of Vault.
     *
     * This matches the semantics of a Consul HTTP health check and provides a simple way to monitor the health of a Vault instance
     */
    public function health(array $arguments = [])
    {
        $url = '/sys/health?' . http_build_query($arguments);

        return $this->client->get($url);
    }
}
