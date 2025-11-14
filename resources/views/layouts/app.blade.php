<?php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'My Laravel App')</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        nav {
            background-color: #333;
            padding: 1rem;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
        }
        nav a:hover {
            text-decoration: underline;
        }
        main {
            padding: 2rem;
        }
        footer {
            background-color: #f0f0f0;
            padding: 1rem;
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/hello">Hello</a>
        <a href="/greeting">Greeting</a>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer>
        <p>&copy; 2025 Boniato Check - My First Laravel App 🪴</p>
    </footer>
</body>
</html>