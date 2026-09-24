<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();
        $response = $next($request);

        $scriptSources = ["'self'", "'nonce-{$nonce}'"];
        $styleSources = ["'self'", "'unsafe-inline'"];
        $fontSources = ["'self'"];
        $imageSources = ["'self'", 'data:', 'blob:'];
        $connectSources = ["'self'"];

        if (app()->environment('local') && ($viteOrigin = $this->localViteOrigin())) {
            $scriptSources[] = $viteOrigin;
            $styleSources[] = $viteOrigin;
            $fontSources[] = $viteOrigin;
            $imageSources[] = $viteOrigin;
            $connectSources[] = $viteOrigin;
            $connectSources[] = preg_replace('/^http/', 'ws', $viteOrigin);
        }

        $policy = implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSources),
            'style-src '.implode(' ', $styleSources),
            'font-src '.implode(' ', $fontSources),
            'img-src '.implode(' ', $imageSources),
            'connect-src '.implode(' ', array_unique($connectSources)),
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Frame-Options', 'DENY');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function localViteOrigin(): ?string
    {
        $url = Vite::devServerUrl();

        if (! $url) {
            return null;
        }

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;
        $normalizedHost = is_string($host) ? trim($host, '[]') : null;

        if (! in_array($scheme, ['http', 'https'], true)
            || ! in_array($normalizedHost, ['localhost', '127.0.0.1', '::1'], true)) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }
}
