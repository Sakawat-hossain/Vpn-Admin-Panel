<?php

namespace Utils;

use Illuminate\Support\Facades\Cache;

class IpInfo
{
    /**
     * Client IP. Uses Laravel's request()->ip(), which only honours
     * X-Forwarded-For from proxies listed in TrustProxies. Cloudflare's
     * CF-Connecting-IP is only used when TRUST_CLOUDFLARE_IP=true.
     */
    public static function ip()
    {
        if (config('services.cloudflare.trust_connecting_ip')) {
            $cfIp = request()->header('CF-Connecting-IP');
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }
        return request()->ip();
    }

    public static function lookup($ip = null)
    {
        $ip = ($ip) ? $ip : self::ip();
        $ipInfo = (object) [];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $cacheKey = 'ipinfo:' . $ip;
            $ipInfo = Cache::get($cacheKey);
            if (!$ipInfo) {
                $fields = "status,country,countryCode,city,zip,lat,lon,timezone,query";
                // ip-api.com's free tier is HTTP-only and rate limited (45 req/min).
                $response = curl_get_file_contents("http://ip-api.com/json/{$ip}?fields={$fields}", 3);
                $ipInfo = (object) (json_decode((string) $response, true) ?: []);
                // Cache successful lookups for a week, failures briefly.
                $ttl = (($ipInfo->status ?? null) === 'success') ? now()->addDays(7) : now()->addMinutes(10);
                Cache::put($cacheKey, $ipInfo, $ttl);
            }
        }
        $data['ip'] = $ipInfo->query ?? $ip;
        $data['location']['country'] = $ipInfo->country ?? "Other";
        $data['location']['country_code'] = $ipInfo->countryCode ?? "Other";
        $data['location']['timezone'] = $ipInfo->timezone ?? "Other";
        $data['location']['city'] = $ipInfo->city ?? "Other";
        $data['location']['postal_code'] = $ipInfo->zip ?? "Unknown";
        $data['location']['latitude'] = $ipInfo->lat ?? "Unknown";
        $data['location']['longitude'] = $ipInfo->lon ?? "Unknown";
        return $data;
    }

}
