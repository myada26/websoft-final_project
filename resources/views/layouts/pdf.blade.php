<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Receipt') — FCATS</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
            color: #0f1f17;
            background: white;
        }
    </style>
    @stack('styles')
</head>

<body>
    @yield('content')
</body>

</html>