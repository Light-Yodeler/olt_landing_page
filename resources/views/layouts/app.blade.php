<!DOCTYPE html>
<html class="scroll-smooth" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $setting?->getTranslation('meta_title', app()->getLocale()) ?? 'Ocean Lombok Tour & Transport' }}</title>
    <meta name="description" content="{{ $setting?->getTranslation('meta_description', app()->getLocale()) ?? '' }}">

    <!-- OpenGraph Tags -->
    <meta property="og:title" content="{{ $setting?->getTranslation('meta_title', app()->getLocale()) ?? 'Ocean Lombok Tour' }}">
    <meta property="og:description" content="{{ $setting?->getTranslation('meta_description', app()->getLocale()) ?? '' }}">
    <meta property="og:type" content="website">
    @if($setting?->og_image_path)
        <meta property="og:image" content="{{ asset('storage/' . $setting->og_image_path) }}">
    @endif

    <!-- Hreflang Canonical Tags -->
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="en" href="{{ url('/') }}">
    <link rel="alternate" hreflang="id" href="{{ url('/id') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    <!-- Favicon -->
    @if($setting?->favicon_path)
        <link rel="icon" href="{{ asset('storage/' . $setting->favicon_path) }}">
    @endif

    <!-- Typography Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-base text-ink-900 selection:bg-ocean-600 selection:text-white">
    @yield('content')
</body>
</html>
