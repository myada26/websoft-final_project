<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') — FCATS</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23166534'/><path d='M50 20 L72 32 L72 56 C72 70 62 80 50 84 C38 80 28 70 28 56 L28 32 Z' fill='%234ade80' opacity='0.9'/><rect x='42' y='44' width='16' height='22' rx='3' fill='%23166534'/><circle cx='50' cy='38' r='8' fill='none' stroke='%23166534' stroke-width='4'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
</head>

<body style="margin:0;font-family:'Outfit',ui-sans-serif,system-ui,sans-serif;background:#f0f3f1;color:#0f1f17">
    @yield('content')
    @livewireScripts
</body>

</html>