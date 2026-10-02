<?php
$branding = App\Models\AppSetting::branding();
$logo = $branding['app_logo'] ? '/storage/' . $branding['app_logo'] : '/icons/kpi-mark.svg';
$favicon = $branding['app_favicon'] ? '/storage/' . $branding['app_favicon'] : $logo;
$ogImage = $branding['app_og_image'] ? '/storage/' . $branding['app_og_image'] : $logo;
$primaryColor = $branding['primary_color'] ?? '#16A34A';
$appName = config('app.name', 'KPI Kepegawaian');
$realAppName = $branding['app_name'] ?? $appName;
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $realAppName }}</title>
        <meta name="theme-color" content="{{ $primaryColor }}">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">

        <!-- Open Graph / SEO -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $realAppName }}">
        <meta property="og:image" content="{{ asset($ogImage) }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $realAppName }}">
        <meta name="twitter:image" content="{{ asset($ogImage) }}">

        <!-- Scripts -->
        @routes(null, \Illuminate\Support\Facades\Vite::cspNonce())
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
