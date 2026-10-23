<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * The one certificate authority and tls-auth key shared by every
 * panel-managed OpenVPN server.
 *
 * Clients (the desktop/mobile apps, downloaded .ovpn profiles) trust this CA
 * and carry the tls-auth key; users then log in with their own credentials,
 * which each server checks against the panel (auth-user-pass-verify).
 *
 * Files live in storage/app/openvpn; the CA private key is stored encrypted
 * with the app key.
 */
class OpenVpnPki
{
    private const CURVE = 'prime256v1';

    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? storage_path('app/openvpn');
    }

    public function exists(): bool
    {
        return is_file($this->path('ca.crt')) && is_file($this->path('ca.key.enc')) && is_file($this->path('ta.key'));
    }

    /**
     * Create the CA and tls-auth key if they don't exist yet. Never replaces
     * existing ones: every installed server and shipped app depends on them.
     */
    public function ensure(): void
    {
        if ($this->exists()) {
            return;
        }
        File::ensureDirectoryExists($this->dir, 0700);

        $key = $this->newKey();
        $csr = openssl_csr_new(['commonName' => $this->caName()], $key, $this->opensslOptions('ca_ext'));
        $cert = $csr ? openssl_csr_sign($csr, null, $key, 3650 * 2, $this->opensslOptions('ca_ext'), random_int(1, PHP_INT_MAX)) : false;
        if (!$cert || !openssl_x509_export($cert, $certPem) || !openssl_pkey_export($key, $keyPem)) {
            throw new RuntimeException('Could not create the OpenVPN CA: ' . $this->opensslErrors());
        }

        $this->write('ca.crt', $certPem);
        $this->write('ca.key.enc', Crypt::encryptString($keyPem));
        $this->write('ta.key', self::newStaticKey());
    }

    public function caCertificate(): string
    {
        $this->ensure();
        return (string) file_get_contents($this->path('ca.crt'));
    }

    public function tlsAuthKey(): string
    {
        $this->ensure();
        return (string) file_get_contents($this->path('ta.key'));
    }

    /**
     * Issue a certificate for an OpenVPN server, signed by the CA.
     * Clients check it with "remote-cert-tls server", so it carries the
     * TLS server extended key usage.
     *
     * @return array{cert: string, key: string}
     */
    public function issueServerCertificate(string $commonName): array
    {
        $this->ensure();
        $caCert = $this->caCertificate();
        $caKey = openssl_pkey_get_private(Crypt::decryptString((string) file_get_contents($this->path('ca.key.enc'))));

        $key = $this->newKey();
        $csr = openssl_csr_new(['commonName' => $commonName], $key, $this->opensslOptions('server_ext'));
        $cert = $csr ? openssl_csr_sign($csr, $caCert, $caKey, 3650, $this->opensslOptions('server_ext'), random_int(1, PHP_INT_MAX)) : false;
        if (!$cert || !openssl_x509_export($cert, $certPem) || !openssl_pkey_export($key, $keyPem)) {
            throw new RuntimeException('Could not issue the OpenVPN server certificate: ' . $this->opensslErrors());
        }
        return ['cert' => $certPem, 'key' => $keyPem];
    }

    /**
     * An OpenVPN static key (the format "openvpn --genkey secret" writes):
     * 2048 random bits as 16 lines of 32 hex digits.
     */
    public static function newStaticKey(): string
    {
        $lines = str_split(bin2hex(random_bytes(256)), 32);
        return "-----BEGIN OpenVPN Static key V1-----\n" . implode("\n", $lines) . "\n-----END OpenVPN Static key V1-----\n";
    }

    private function newKey()
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => self::CURVE] + $this->opensslOptions());
        if (!$key) {
            throw new RuntimeException('Could not create an EC key: ' . $this->opensslErrors());
        }
        return $key;
    }

    private function caName(): string
    {
        $site = trim((string) (settings('general')->site_name ?? config('app.name')));
        return substr(($site !== '' ? $site : 'VPN') . ' OpenVPN CA', 0, 64);
    }

    /**
     * openssl_* options pointing at a minimal config with our x509 extensions,
     * so it doesn't depend on the system openssl.cnf.
     */
    private function opensslOptions(?string $extensions = null): array
    {
        $config = $this->path('openssl.cnf');
        if (!is_file($config)) {
            File::ensureDirectoryExists($this->dir, 0700);
            $this->write('openssl.cnf', implode("\n", [
                '[ req ]',
                'distinguished_name = dn',
                'default_bits = 2048',
                '[ dn ]',
                '[ ca_ext ]',
                'basicConstraints = critical, CA:true',
                'keyUsage = critical, keyCertSign, cRLSign',
                'subjectKeyIdentifier = hash',
                '[ server_ext ]',
                'basicConstraints = CA:false',
                'keyUsage = critical, digitalSignature, keyAgreement',
                'extendedKeyUsage = serverAuth',
                'subjectKeyIdentifier = hash',
                'authorityKeyIdentifier = keyid',
                '',
            ]));
        }
        $options = ['config' => $config, 'digest_alg' => 'sha256'];
        if ($extensions) {
            $options['x509_extensions'] = $extensions;
        }
        return $options;
    }

    private function path(string $file): string
    {
        return $this->dir . '/' . $file;
    }

    private function write(string $file, string $contents): void
    {
        File::put($this->path($file), $contents);
        @chmod($this->path($file), 0600);
    }

    private function opensslErrors(): string
    {
        $errors = [];
        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }
        return implode('; ', $errors) ?: 'unknown error';
    }
}
