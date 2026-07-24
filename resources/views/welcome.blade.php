<!DOCTYPE html>
<html lang="en" class="h-full antialiased" :class="{ 'dark': $store.theme.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transport Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-zinc-50 px-4 dark:bg-zinc-950">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-lg font-bold text-white">T</div>
        <h1 class="mt-6 text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Transport Management System</h1>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Please sign in to continue to your dashboard.</p>
        <a href="{{ route('login') }}" class="mt-6 inline-flex h-10 items-center justify-center rounded-lg bg-brand-600 px-6 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">Sign in</a>
    </div>
</body>
</html>