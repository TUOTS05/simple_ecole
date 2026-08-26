<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('icons/log.jpg.jpeg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('components.numeric-guard-script')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 pb-10 px-4 bg-gradient-to-br from-blue-50 via-white to-green-50 relative overflow-hidden">
            <!-- Décoration d'arrière-plan -->
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <div class="absolute top-10 left-10 w-72 h-72 bg-blue-500 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-72 h-72 bg-green-500 rounded-full blur-3xl"></div>
            </div>

            <div class="relative">
                <a href="/" class="flex items-center justify-center gap-2 mb-8">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-green-500 rounded-xl flex items-center justify-center shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Simple <span class="text-primary">Ecole</span></span>
                </a>
            </div>

            <div class="relative w-full sm:max-w-md px-6 py-8 sm:px-10 bg-white shadow-xl border border-gray-100 overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>

            <p class="relative mt-8 text-sm text-gray-400">
                &copy; {{ date('Y') }} Simple Ecole. Tous droits réservés.
            </p>
        </div>
    </body>
</html>
