<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class TrustConfiguredProxies extends TrustProxies
{
    /**
     * @return array<int, string>
     */
    protected function proxies(): array
    {
        $configured = config('trustedproxy.proxies', '');
        $proxies = is_array($configured) ? $configured : explode(',', (string) $configured);

        return array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            $proxies,
        )));
    }

    protected function headers(): int
    {
        return Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_PREFIX;
    }
}
