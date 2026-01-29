{{--
    * LAYOUT: Main application layout
    * All pages extend this template
    * Uses: @yield('title'), @yield('content'), @stack('scripts'), @stack('styles')
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
    {{-- * Alpine.js for reactive UI without full SPA --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen">
    @yield('content')
    
    @stack('scripts')
</body>
</html>