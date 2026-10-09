<?php

namespace App\Services;

use App\Exceptions\WgEasyException;
use App\Models\Server;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Thin client for the wg-easy API (ombapit/wg-easy fork) running on a VPN server.
 *
 * Notes on the fork's behaviour this relies on:
 *  - the client id IS the name we create it with ("wg{userId}"), so creating
 *    the same name again replaces that peer with fresh keys and a new address;
 *  - when the container has a PASSWORD, API calls must send it verbatim in the
 *    Authorization header;
 *  - addresses come from one /24, so a server holds at most 253 peers.
 */
class WgEasyClient
{
    private Server $server;
    private Client $http;

    public function __construct(Server $server, ?Client $http = null)
    {
        $this->server = $server;
        $this->http = $http ?: new Client([
            'base_uri' => self::baseUri($server),
            'timeout' => config('services.wg_easy.timeout', 10),
            'connect_timeout' => min(5, config('services.wg_easy.timeout', 10)),
            'http_errors' => true,
        ]);
    }

    public static function for(Server $server): self
    {
        // Resolved through the container so tests can swap in a fake.
        return app()->makeWith(self::class, ['server' => $server, 'http' => null]);
    }

    public static function baseUri(Server $server): string
    {
        $host = $server->ip_address;
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = "[$host]";
        }
        return 'http://' . $host . ':' . config('services.wg_easy.api_port', 51821);
    }

    /**
     * @return array<int, array> peers with id, name, enabled, address, transferRx, transferTx, ...
     */
    public function listClients(): array
    {
        $body = $this->request('GET', '/api/wireguard/client', ['headers' => ['Accept' => 'application/json']]);
        $clients = json_decode($body, true);
        if (!is_array($clients)) {
            throw new WgEasyException("Unexpected response from wg-easy on {$this->server->ip_address}");
        }
        return $clients;
    }

    public function createClient(string $name): void
    {
        $this->request('POST', '/api/wireguard/client', [
            'headers' => ['Accept' => 'application/json'],
            'json' => ['name' => $name],
        ]);
    }

    public function getConfiguration(string $clientId, string $dns): string
    {
        return $this->request('GET', '/api/wireguard/client/' . rawurlencode($clientId) . '/' . rawurlencode($dns) . '/configuration', [
            'headers' => ['Accept' => 'text/plain'],
        ]);
    }

    public function deleteClient(string $clientId): void
    {
        $this->request('DELETE', '/api/wireguard/client/' . rawurlencode($clientId));
    }

    private function request(string $method, string $path, array $options = []): string
    {
        if (!empty($this->server->wg_password)) {
            $options['headers']['Authorization'] = $this->server->wg_password;
        }
        try {
            return (string) $this->http->request($method, $path, $options)->getBody();
        } catch (GuzzleException $e) {
            throw new WgEasyException(
                "wg-easy {$method} {$path} on {$this->server->ip_address} failed: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }
}
