<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_response_has_restrictive_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("script-src 'self'", $policy);
        $this->assertMatchesRegularExpression("/'nonce-[A-Za-z0-9]+'/", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
        $this->assertStringNotContainsString('unsafe-eval', $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
    }

    public function test_login_scripts_receive_the_csp_nonce(): void
    {
        $response = $this->get('/login')->assertOk();
        $policy = (string) $response->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", $policy, $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count((string) $response->getContent(), 'nonce="'.($matches[1] ?? '').'"'),
        );
    }

    public function test_local_loopback_vite_server_is_allowed_without_unsafe_inline_scripts(): void
    {
        $originalEnvironment = app()->environment();
        $hotFile = Vite::hotFile();
        $originalHotFile = is_file($hotFile) ? file_get_contents($hotFile) : null;

        try {
            app()->detectEnvironment(fn () => 'local');
            file_put_contents($hotFile, 'http://127.0.0.1:5173');

            $response = $this->get('/login')->assertOk();
            $policy = (string) $response->headers->get('Content-Security-Policy');

            $this->assertStringContainsString("script-src 'self'", $policy);
            $this->assertStringContainsString('http://127.0.0.1:5173', $policy);
            $this->assertStringContainsString('ws://127.0.0.1:5173', $policy);
            $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);

            if ($originalHotFile === null) {
                @unlink($hotFile);
            } else {
                file_put_contents($hotFile, $originalHotFile);
            }
        }
    }

    public function test_https_response_enables_hsts(): void
    {
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_trusted_reverse_proxy_preserves_https_host_redirect_and_hsts(): void
    {
        $this->app->make(HttpKernel::class);
        config()->set('trustedproxy.proxies', '127.0.0.1, 10.10.0.0/16');
        $this->registerProxyProbe();

        $response = $this
            ->withHeaders([
                'X-Forwarded-Host' => 'kpi.example.test',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('http://internal.example.test/_test/proxy-request');

        $response
            ->assertOk()
            ->assertJson([
                'host' => 'kpi.example.test',
                'secure' => true,
                'url' => 'https://kpi.example.test/_test/proxy-request',
            ])
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_forwarded_https_headers_from_untrusted_client_are_ignored(): void
    {
        $this->app->make(HttpKernel::class);
        config()->set('trustedproxy.proxies', ['10.10.0.10']);
        $this->registerProxyProbe();

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.25'])
            ->withHeaders([
                'Host' => 'internal.example.test',
                'X-Forwarded-Host' => 'kpi.example.test',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('http://internal.example.test/_test/proxy-request');

        $response
            ->assertOk()
            ->assertJson([
                'host' => 'internal.example.test',
                'secure' => false,
                'url' => 'http://internal.example.test/_test/proxy-request',
            ]);
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $email = 'tidak-ada@example.test';
        $key = Str::transliterate(Str::lower($email).'|127.0.0.1');
        RateLimiter::clear($key);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $email, 'password' => 'salah'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $email, 'password' => 'salah'])
            ->assertTooManyRequests();
    }

    public function test_self_registration_routes_remain_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    private function registerProxyProbe(): void
    {
        Route::get('/_test/proxy-request', fn (Request $request) => response()->json([
            'host' => $request->getHost(),
            'secure' => $request->isSecure(),
            'url' => $request->fullUrl(),
        ]))->middleware('web');
    }
}
