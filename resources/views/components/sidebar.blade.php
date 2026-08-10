<!-- Sidebar -->
<aside
    x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }"
    x-init="$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))"
    :class="sidebarOpen ? 'w-64' : 'w-20'"
    class="bg-white shadow-lg flex flex-col h-screen sticky top-0 transition-all duration-300 ease-in-out overflow-hidden"
>

    <!-- Logo & Bouton de réduction -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 flex-shrink-0">
        <h1 x-show="sidebarOpen" x-transition class="text-xl font-bold text-primary tracking-tight whitespace-nowrap">
            SaaS_Ecole
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

        @elseif(method_exists(auth()->user(), 'isParent') && auth()->user()->isParent())
        <!-- ========================================== -->
        <!-- MENU PARENT                                -->
        <!-- ========================================== -->
        <a href="{{ route('parent.dashboard') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('parent.dashboard') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">🏠</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Tableau de bord</span>
        </a>

        @php
            $firstChild = auth()->user()->children->first();
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

        <a href="{{ route('parent.messages.index') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('parent.messages.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">✉️</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Messages</span>
        </a>

        @else
        <!-- ========================================== -->
        <!-- MENU ADMIN ÉCOLE                           -->
        <!-- ========================================== -->
        <a href="{{ route('app.dashboard') }}" title="Tableau de bord" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('app.dashboard') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📊</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Tableau de bord</span>
        </a>

        <!-- Groupe : Scolarité & Finances -->
        <div x-data="{ open: {{ request()->routeIs('app.enrollments.*') || request()->routeIs('app.payments.*') || request()->routeIs('app.class-fees.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Scolarité & Finances"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">💰</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Scolarité & Finances</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                <a href="{{ route('app.enrollments.index') }}" title="Inscriptions" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.enrollments.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Inscriptions</a>
                <a href="{{ route('app.payments.index') }}" title="Paiements" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.payments.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Paiements</a>
            </div>
        </div>

        <!-- Groupe : Vie Scolaire -->
        <div x-data="{ open: {{ request()->routeIs('app.students.*') || request()->routeIs('app.attendances.*') || request()->routeIs('app.report-cards.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Vie Scolaire"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">🎓</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Vie Scolaire</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                <a href="{{ route('app.students.index') }}" title="Liste des Élèves" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.students.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Élèves</a>
                <a href="{{ route('app.attendances.index') }}" title="Présences" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.attendances.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Présences</a>
                <a href="{{ route('app.report-cards.index') }}" title="Bulletins" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.report-cards.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Bulletins</a>
            </div>
        </div>

        @if(auth()->user()->isSchoolAdmin())
        <!-- Groupe : Ressources Humaines -->
        <div x-data="{ open: {{ request()->routeIs('app.teachers.*') || request()->routeIs('app.teacher-assignments.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Personnel Enseignant"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">👨‍🏫</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Personnel</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                <a href="{{ route('app.teachers.index') }}" title="Liste des Enseignants" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.teachers.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Enseignants</a>
                <a href="{{ route('app.teacher-assignments.index') }}" title="Assignations" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.teacher-assignments.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Assignations</a>
            </div>
        </div>

         <!-- ✅ GROUPE : RAPPORTS (Basé sur vos routes réelles web.php) -->
        <div x-data="{ open: {{ request()->routeIs('app.report-cards.*') || request()->routeIs('app.financial.*') || (request()->routeIs('app.students.index') && request()->has('export')) ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Rapports"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">📈</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Rapports</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                
                <!-- 1. Bulletins de notes -->
                <a href="{{ route('app.report-cards.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.report-cards.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Bulletins de notes
                </a>
                
                <!-- 2. États de scolarité (Impayés par classe & Détails élève) -->
                <a href="{{ route('app.financial.unpaid_by_class') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.financial.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    États de scolarité
                </a>
                
                <!-- 3. Listes de classe (La vue index contient vos boutons d'export Excel/PDF) -->
                <a href="{{ route('app.students.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.students.index') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Listes de classe & Exports
                </a>

                <!-- 4. Rapport de présences (Historique et exports) -->
                <a href="{{ route('app.attendances.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.attendances.index') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Rapport Présences/Absences
                </a>

            </div>
        </div>

                <!-- ========================================== -->
        <!-- GROUPE : CANTINE SCOLAIRE                  -->
        <!-- ========================================== -->
        <div x-data="{ open: {{ request()->routeIs('canteen.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Cantine Scolaire"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">🍽️</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Cantine Scolaire</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                <!-- 1. Configuration des Tarifs -->
                <a href="{{ route('canteen.rates.index') }}" 
                   class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.rates.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Configuration des Tarifs
                </a>
                
                <!-- 2. Inscriptions des Élèves -->
                <a href="{{ route('canteen.subscriptions.index') }}" 
                   class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.subscriptions.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Inscriptions des Élèves
                </a>
                
                <!-- 3. Paiements Cantine -->
                <a href="{{ route('canteen.payments.index') }}" 
                   class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.payments.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Paiements Cantine
                </a>
                
                <!-- 4. Rapports Cantine (Impayés par classe, etc.) -->
                <a href="{{ route('canteen.reports.unpaid_by_class') }}" 
                   class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.reports.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Rapports Cantine
                </a>
            </div>
        </div>

        <!-- Groupe : Paramètres de l'école -->
        <div x-data="{ open: {{ request()->routeIs('app.classes.*') || request()->routeIs('app.subjects.*') || request()->routeIs('app.school-years.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Paramètres"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">⚙️</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Paramètres</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                <a href="{{ route('app.school-years.index') }}" title="Années Scolaires" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.school-years.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Années</a>
                <a href="{{ route('app.class-fees.index') }}" title="Configuration des Frais" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.class-fees.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Frais</a>
                <a href="{{ route('app.classes.index') }}" title="Classes" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.classes.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Classes</a>
                <a href="{{ route('app.subjects.index') }}" title="Matières" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.subjects.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Matières</a>
            </div>
        </div>
        @endif

        <!-- Communication (Visible par Admin École et Parent) -->
        <a href="{{ route('app.messages.index') }}" title="Messages"
            class="flex items-center justify-between px-3 py-3 rounded-lg transition mt-2 {{ request()->routeIs('app.messages.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <div class="flex items-center">
                <span class="text-xl min-w-[24px] text-center">✉️</span>
                <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Messages</span>
            </div>
            @php
            $currentSchoolId = session('current_school_id') ?? auth()->user()->school_id;
            $unreadMessages = \App\Models\Message::where('school_id', $currentSchoolId)->where('is_read', false)->count();
            @endphp
            <span x-show="sidebarOpen && {{ $unreadMessages }} > 0" class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-sm">{{ $unreadMessages }}</span>
        </a>
        @endif

    </nav>

    <!-- Footer Sidebar -->
    <div class="p-3 border-t border-gray-200 flex-shrink-0">
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