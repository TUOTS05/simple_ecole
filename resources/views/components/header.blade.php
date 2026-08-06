<!-- Header -->
<!-- Header -->
<header class="h-16 bg-white shadow-md border-b-4 border-primary flex items-center justify-between px-6">
    
    <!-- Titre de la page -->
    <div>
        <h2 class="text-xl font-semibold text-gray-800">
            @yield('page_title', 'Dashboard')
        </h2>
    </div>
    
    <!-- Infos utilisateur -->
    <div class="flex items-center space-x-4">
        
        @if(session('current_school'))
            <!-- Nom de l'école (pour Admin École) -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">École :</span>
                <span class="font-semibold text-primary">
                    {{ session('current_school')->name }}
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
                    @if(auth()->user()->isSuperAdmin())
                        Super Admin
                    @else
                        Admin École
                    @endif
                </div>
            </div>
        </div>
        
    </div>
    
</header>