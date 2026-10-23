<?php

namespace App\Services;

use App\Models\Server;

/**
 * Client profile (.ovpn) for a panel-managed OpenVPN server. It holds no
 * secret of its own: users sign in with their account email and password
 * (or an app session's VPN credentials), which the server checks with the
 * panel. Settings match what the client apps use (see the IVPN client's
 * generated config) so both kinds of client work with the same server.
 */
class OpenVpnProfile
{
    public function __construct(private OpenVpnPki $pki)
    {
    }

    /**
     * The profile handed to users of an OpenVPN server: generated for
     * panel-managed servers, the pasted one otherwise ('' if none).
     */
    public function clientProfile(Server $server): string
    {
        if ($server->isManagedOpenVpn()) {
            return $this->forServer($server);
        }
        return (string) $server->ovpn_config;
    }

    public function forServer(Server $server): string
    {
        $proto = config('services.openvpn.proto') === 'tcp' ? 'tcp' : 'udp';
        $lines = [
            '# ' . (settings('general')->site_name ?? '') . ' - ' . $server->country . ' ' . $server->state,
            'client',
            'dev tun',
            'proto ' . $proto,
            'remote ' . $server->ip_address . ' ' . (int) config('services.openvpn.port'),
            'resolv-retry infinite',
            'nobind',
            'persist-key',
            'persist-tun',
            'remote-cert-tls server',
            'auth-user-pass',
            'auth-nocache',
            'cipher AES-256-CBC',
            'data-ciphers AES-256-GCM:AES-128-GCM:CHACHA20-POLY1305:AES-256-CBC',
            'verb 3',
            '<ca>',
            trim($this->pki->caCertificate()),
            '</ca>',
        ];
        if (self::tlsMode() === 'tls-auth') {
            array_push($lines, 'key-direction 1', '<tls-auth>', trim($this->pki->tlsAuthKey()), '</tls-auth>');
        } else {
            array_push($lines, '<tls-crypt>', trim($this->pki->tlsAuthKey()), '</tls-crypt>');
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * "tls-crypt" or "tls-auth" (see config services.openvpn.tls).
     */
    public static function tlsMode(): string
    {
        return config('services.openvpn.tls') === 'tls-auth' ? 'tls-auth' : 'tls-crypt';
    }
}
