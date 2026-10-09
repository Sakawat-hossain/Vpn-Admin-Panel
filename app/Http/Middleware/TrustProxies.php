<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Proxies from TRUSTED_PROXIES (comma-separated IPs/CIDRs, or "*").
     * Only these may set X-Forwarded-For, so client IPs can't be spoofed.
     */
    protected function proxies()
    {
        $proxies = config('app.trusted_proxies');
        if ($proxies === null || $proxies === '') {
            return $this->proxies;
        }
        return $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies));
    }
}
