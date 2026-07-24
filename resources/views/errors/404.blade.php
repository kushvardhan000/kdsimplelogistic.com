<!DOCTYPE html>
<html lang="en" class="h-full antialiased" :class="{ 'dark': $store.theme.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 · Not Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-zinc-50 px-4 dark:bg-zinc-950">
    <div class="w-full max-w-md text-center">
        <h1 class="text-6xl font-bold text-zinc-900 dark:text-zinc-50">404</h1>
        <p class="mt-2 text-lg text-zinc-600 dark:text-zinc-400">Page Not Found</p>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">The page you are looking for does not exist or has been moved.</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex h-10 items-center justify-center rounded-lg bg-brand-600 px-6 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">Go Home</a>
    </div>
</body>
</html>
