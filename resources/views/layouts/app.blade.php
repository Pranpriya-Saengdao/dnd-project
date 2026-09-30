<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'D&D Web Game')</title>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Pixelify+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/game.css') }}?v={{ file_exists(public_path('css/game.css')) ? filemtime(public_path('css/game.css')) : time() }}">
    @vite(['resources/js/app.js'])
</head>
<body>
<div class="wrap">
    <header class="topbar">
        <a class="logo" href="{{ auth()->check() ? route('lobby') : route('home') }}">D&amp;D Web Game</a>
        <nav>
            @auth
                <span>{{ auth()->user()->username }}</span>
                @if(auth()->user()->isAdmin())<a class="btn sm gold" href="{{ route('admin.dashboard') }}">Admin</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn sm">Logout</button></form>
            @else
                <a class="btn sm gold" href="{{ route('login') }}">Login</a>
            @endauth
        </nav>
    </header>
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
