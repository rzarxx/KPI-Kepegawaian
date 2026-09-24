<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    public function test_manifest_exposes_internal_app_metadata(): void
    {
        $path = public_path('manifest.webmanifest');
        $this->assertFileExists($path);
        $manifest = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Sistem Penilaian Karyawan', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));
        foreach (['kpi-64.png' => [64, 64], 'kpi-192.png' => [192, 192], 'kpi-512.png' => [512, 512], 'kpi-maskable-512.png' => [512, 512], 'apple-touch-icon.png' => [180, 180]] as $file => $size) {
            $icon = public_path('icons/'.$file);
            $this->assertFileExists($icon);
            $this->assertSame($size, array_slice(getimagesize($icon), 0, 2));
        }
    }

    public function test_service_worker_contains_static_only_cache_policy(): void
    {
        $path = public_path('sw.js');
        $this->assertFileExists($path);
        $this->assertStringContainsString('STATIC_DESTINATIONS', file_get_contents($path));
        $this->assertStringContainsString("request.method !== 'GET'", file_get_contents($path));
        $this->assertStringNotContainsString('api/', file_get_contents($path));
        $this->assertStringContainsString("request.mode === 'navigate'", file_get_contents($path));
        $this->assertStringContainsString('/icons/kpi-192.png', file_get_contents($path));
        $this->assertStringContainsString('/icons/kpi-maskable-512.png', file_get_contents($path));
        $this->assertStringNotContainsString('/karyawan', file_get_contents($path));
        $this->assertStringNotContainsString('/laporan', file_get_contents($path));
    }
}
