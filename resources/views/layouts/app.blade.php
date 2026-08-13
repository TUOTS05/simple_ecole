<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EcoleTUO')</title>
    
    <!-- Tailwind CSS (via CDN pour commencer) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Configuration Tailwind avec couleurs pastel -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#87CEEB',      // Bleu ciel
                        'primary-dark': '#5FB3D9',
                        'secondary': '#FFF9C4',    // Jaune pastel
                        'accent': '#A5D6A7',       // Vert pastel
                        'danger': '#EF9A9A',       // Rouge pastel
                    }
                }
            }
        }
    </script>
    
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        
        
        <!-- SIDEBAR -->
        @include('components.sidebar')
        
        <!-- CONTENU PRINCIPAL -->
        <div class="flex-1 flex flex-col overflow-hidden">
            @include('components.trial-banner')
            
            <!-- HEADER -->
            @include('components.header')
            
            <!-- ZONE DE CONTENU -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
            
        </div>
    </div>
    
    @stack('scripts')
</body>
</html>