{{--
    * LAYOUT: Main application layout
    * All pages extend this template
    * Uses: @yield('title'), @yield('content'), @stack('scripts'), @stack('styles')
    * Livewire v4 bundles Alpine.js - no separate CDN needed
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- * CSRF token for AJAX requests - used by fetch/axios --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Online School Management')</title>
    <link rel="icon" href="{{ asset($favicon ?? 'favicon.svg') }}" type="image/svg+xml">

    {{-- * Vite bundles CSS and JS from resources/ --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen">
    @yield('content')

    @livewireScripts
    @stack('scripts')
</body>
</html>