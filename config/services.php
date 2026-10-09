<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
     */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/login/facebook/callback',
    ],

    // wg-easy (WireGuard) servers provisioned by App\Jobs\ConfigServer.
    'wg_easy' => [
        // Pinned by digest so every server runs the exact build the panel was
        // written against (fork exposes /api/wireguard/client/{id}/{dns}/configuration
        // and accepts the password in the Authorization header).
        'image' => env('WG_EASY_IMAGE', 'ombapit/wg-easy@sha256:f9f7a07dc8a7f19703760f0a410c78b8df88b694169209a0fb93d8f476ddb5d8'),
        'api_port' => (int) env('WG_EASY_API_PORT', 51821),
        'wg_port' => (int) env('WG_EASY_WG_PORT', 51820),
        // Comma-separated IPs/CIDRs allowed to reach the wg-easy API port.
        // Empty = the IP the panel's SSH session comes from (detected on the VPS).
        'allowed_ips' => env('WG_EASY_ALLOWED_IPS', ''),
        'persistent_keepalive' => (int) env('WG_EASY_PERSISTENT_KEEPALIVE', 25),
        'timeout' => (float) env('WG_EASY_TIMEOUT', 10),
        'default_dns' => env('WG_EASY_DEFAULT_DNS', '1.1.1.1'),
    ],

    // Only honour Cloudflare's CF-Connecting-IP header when the site really sits
    // behind Cloudflare; otherwise any client could spoof its logged IP.
    'cloudflare' => [
        'trust_connecting_ip' => (bool) env('TRUST_CLOUDFLARE_IP', false),
    ],
];
