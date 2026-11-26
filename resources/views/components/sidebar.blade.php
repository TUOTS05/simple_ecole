<!-- Sidebar -->
<aside
    x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }"
    x-init="$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))"
    :class="sidebarOpen ? 'w-64' : 'w-20'"
    class="bg-white shadow-lg flex flex-col h-screen sticky top-0 transition-all duration-300 ease-in-out overflow-hidden">

    <!-- Logo & Bouton de réduction -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 flex-shrink-0">
        <h1 x-show="sidebarOpen" x-transition class="text-xl font-bold text-primary tracking-tight whitespace-nowrap">
            Simple Ecole
        </h1>
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 focus:outline-none transition-colors" title="Réduire/Agrandir le menu">
            <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>

    <nav class="flex-1 p-2 space-y-1 overflow-y-auto custom-scrollbar">

        @if(auth()->user()->isSuperAdmin())
        <!-- ========================================== -->
        <!-- MENU SUPER ADMIN                           -->
        <!-- ========================================== -->
        <a href="{{ route('superadmin.dashboard') }}" title="Dashboard" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.dashboard') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📊</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Dashboard</span>
        </a>
        <a href="{{ route('superadmin.schools.index') }}" title="Écoles" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.schools.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">🏫</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Écoles</span>
        </a>
        <a href="{{ route('superadmin.plans.index') }}" title="Plans d'abonnement" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.plans.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">💎</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Plans d'abonnement</span>
        </a>
        <a href="{{ route('superadmin.subscriptions.index') }}" title="Abonnements" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.subscriptions.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📅</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Abonnements</span>
        </a>
        @php
        $pendingRequestsCount = \App\Models\SubscriptionRequest::where('status', 'pending')->count();
        @endphp
        <a href="{{ route('superadmin.subscriptions.pending') }}" title="Demandes d'abonnement" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.subscriptions.pending') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📩</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Demandes d'abonnement</span>
            @if($pendingRequestsCount > 0)
            <span x-show="sidebarOpen" class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingRequestsCount }}</span>
            @endif
        </a>
        <a href="{{ route('superadmin.users.index') }}" title="Utilisateurs" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.users.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">👥</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Utilisateurs</span>
        </a>
        <div x-show="sidebarOpen" class="pt-4 pb-2 px-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Paramètres</p>
        </div>
        <a href="{{ route('superadmin.activity-logs.index') }}" title="Journaux d'activité" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.activity-logs.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📜</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Journaux d'activité</span>
        </a>
        <a href="{{ route('superadmin.settings.edit') }}" title="Paramètres Système" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('superadmin.settings.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">⚙️</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Paramètres Système</span>
        </a>

        @elseif(method_exists(auth()->user(), 'isTeacher') && auth()->user()->isTeacher())
        <!-- ========================================== -->
        <!-- MENU ENSEIGNANT                            -->
        <!-- ========================================== -->
        <a href="{{ route('teacher.dashboard') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('teacher.dashboard') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📊</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Tableau de bord</span>
        </a>
        <a href="{{ route('teacher.classes') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('teacher.classes') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">🏫</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Mes Classes</span>
        </a>
        <a href="{{ route('teacher.attendance.index') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('teacher.attendance.index') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">✅</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Historique Absences</span>
        </a>

        {{-- Droits supplémentaires accordés par le directeur (aucun par défaut) --}}
        @include('components.sidebar-staff-menu')

        @elseif(method_exists(auth()->user(), 'isParent') && auth()->user()->isParent())
        <!-- ========================================== -->
        <!-- MENU PARENT                                -->
        <!-- ========================================== -->
        <a href="{{ route('parent.dashboard') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('parent.dashboard') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">🏠</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Tableau de bord</span>
        </a>

                        @php
                        // 1. Définition de $firstChild (indispensable pour les liens Bulletins, Présences, Paiements)
                        $firstChild = auth()->user()->children->first();
                        
                        // 2. Calcul des messages non lus (voir Message::scopeUnreadForParent)
                        $parentUnreadMessages = auth()->user()->isParent()
                            ? \App\Models\Message::unreadForParent(auth()->user())->count()
                            : 0;
                        @endphp

        @if($firstChild)
        <a href="{{ route('parent.grades.index', $firstChild->id) }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('parent.grades.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📄</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Bulletins & Notes</span>
        </a>
        <a href="{{ route('parent.attendance.index', $firstChild->id) }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('parent.attendance.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">✅</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Présences</span>
        </a>
        <a href="{{ route('parent.payments.index', $firstChild->id) }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('parent.payments.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">💳</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Paiements</span>
        </a>
        @endif

        <!-- ✅ LIEN MESSAGES PARENT AVEC BADGE DE NOTIFICATION -->
        <a href="{{ route('parent.messages.index') }}" class="flex items-center justify-between px-3 py-3 rounded-lg transition {{ request()->routeIs('parent.messages.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <div class="flex items-center">
                <span class="text-xl min-w-[24px] text-center">✉️</span>
                <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Messages</span>
            </div>
            @if($parentUnreadMessages > 0)
            <span x-show="sidebarOpen" class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm">
                {{ $parentUnreadMessages }}
            </span>
            @endif
        </a>

        @else
        <!-- ========================================== -->
        <!-- MENU DU PERSONNEL DE L'ÉCOLE (selon les droits) -->
        <!-- ========================================== -->
        @include('components.sidebar-staff-menu')
        @endif

    </nav>
    <!-- Footer Sidebar -->
    <div class="p-3 border-t border-gray-200 flex-shrink-0 space-y-2">
        
        @if(auth()->user()->isSchoolAdmin())
            <a href="{{ route('app.profile.edit') }}" title="Mon Profil" class="flex items-center justify-center px-3 py-2 rounded-lg hover:bg-gray-100 transition text-gray-700 text-sm font-medium">
                <svg class="w-5 h-5 min-w-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span x-show="sidebarOpen" class="ml-2 whitespace-nowrap">Mon Profil</span>
            </a>

        @elseif(auth()->user()->isTeacher())
            <a href="{{ route('teacher.profile.index') }}" title="Mon Profil" class="flex items-center justify-center px-3 py-2 rounded-lg hover:bg-gray-100 transition text-gray-700 text-sm font-medium">
                <svg class="w-5 h-5 min-w-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span x-show="sidebarOpen" class="ml-2 whitespace-nowrap">Mon Profil</span>
            </a>

        @elseif(method_exists(auth()->user(), 'isAccountant') && auth()->user()->isAccountant())
            <a href="{{ route('app.accountant-profile.edit') }}" title="Mon Profil" class="flex items-center justify-center px-3 py-2 rounded-lg hover:bg-gray-100 transition text-gray-700 text-sm font-medium">
                <svg class="w-5 h-5 min-w-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span x-show="sidebarOpen" class="ml-2 whitespace-nowrap">Mon Profil</span>
            </a>

        @elseif(auth()->user()->isStaff())
            <a href="{{ route('profile.edit') }}" title="Mon Profil" class="flex items-center justify-center px-3 py-2 rounded-lg hover:bg-gray-100 transition text-gray-700 text-sm font-medium">
                <svg class="w-5 h-5 min-w-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span x-show="sidebarOpen" class="ml-2 whitespace-nowrap">Mon Profil</span>
            </a>

        {{-- ✅ SOLUTION ULTIME : Vérifie le rôle OU la présence d'enfants --}}
        @elseif(auth()->user()->isParent() || (method_exists(auth()->user(), 'children') && auth()->user()->children->isNotEmpty()))
            <a href="{{ route('parent.profile.edit') }}" title="Mon Profil" class="flex items-center justify-center px-3 py-2 rounded-lg hover:bg-gray-100 transition text-gray-700 text-sm font-medium">
                <svg class="w-5 h-5 min-w-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span x-show="sidebarOpen" class="ml-2 whitespace-nowrap">Mon Profil</span>
            </a>
        @endif

        <!-- Bouton Déconnexion -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Déconnexion" class="w-full flex items-center justify-center px-3 py-2.5 rounded-lg bg-gray-100 hover:bg-red-50 hover:text-red-600 transition text-gray-700 text-sm font-medium">
                <svg class="w-5 h-5 min-w-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span x-show="sidebarOpen" class="ml-3 whitespace-nowrap">Déconnexion</span>
            </button>
        </form>
    </div>
</aside>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 20px;
    }
</style>