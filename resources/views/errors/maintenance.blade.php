<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $platformName ?? config('app.name') }} — Maintenance</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center pt-10 pb-10 px-4 bg-gradient-to-br from-blue-50 via-white to-green-50 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <div class="absolute top-10 left-10 w-72 h-72 bg-blue-500 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-72 h-72 bg-green-500 rounded-full blur-3xl"></div>
            </div>

            <div class="relative w-full sm:max-w-md px-6 py-10 sm:px-10 bg-white shadow-xl border border-gray-100 overflow-hidden sm:rounded-2xl text-center">
                <div class="text-5xl mb-4">🔧</div>
                <h1 class="text-xl font-bold text-gray-900 mb-2">{{ $platformName ?? config('app.name') }}</h1>
                <p class="text-gray-600">
                    {{ $message ?: "Nous effectuons une maintenance programmée. Le service sera de retour dans quelques minutes." }}
                </p>
            </div>

            <p class="relative mt-8 text-sm text-gray-400">
                &copy; {{ date('Y') }} {{ $platformName ?? config('app.name') }}. Tous droits réservés.
            </p>
        </div>
    </body>
</html>
