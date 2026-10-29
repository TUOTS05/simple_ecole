<!-- En-tête -->
<header class="h-16 bg-white shadow-md border-b-4 border-primary flex items-center justify-between px-4 md:px-6 gap-4">

    <!-- Titre de la page (une seule ligne, tronquée sur petit écran) -->
    <div class="min-w-0">
        <h2 class="text-lg md:text-xl font-semibold text-gray-800 truncate">
            @yield('page_title', 'Dashboard')
        </h2>
    </div>

    <!-- Infos utilisateur : masquées sur mobile, faute de place -->
    <div class="hidden md:flex items-center space-x-4 flex-shrink-0">

        {{-- AFFICHAGE DU NOM DE L'ÉCOLE --}}
        @if(session('current_school'))
        @php
        // On vérifie si c'est un objet ou un tableau pour éviter l'erreur "Attempt to read property on array"
        $school = session('current_school');
        $schoolName = is_object($school) ? $school->name : ($school['name'] ?? 'École inconnue');
        @endphp

        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-600">École :</span>
            <span class="font-semibold text-primary">
                {{ $schoolName }}
            </span>
        </div>
        @endif

        {{-- AFFICHAGE DE L'ANNÉE SCOLAIRE ACTIVE --}}
        @if(session('current_school_year'))
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-600">Année :</span>
            <span class="font-semibold text-primary">
                {{ session('current_school_year')->name }}
            </span>
        </div>
        @endif

        <!-- Avatar + Nom -->
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold">
                {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
            </div>
            <div>
                <div class="text-sm font-semibold text-gray-800">
                    {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ auth()->user()->roleLabel() }}
                </div>
            </div>
        </div>

    </div>

</header>