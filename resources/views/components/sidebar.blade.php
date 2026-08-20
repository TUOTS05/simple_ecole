<!-- Sidebar -->
<aside
    x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }"
    x-init="$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))"
    :class="sidebarOpen ? 'w-64' : 'w-20'"
    class="bg-white shadow-lg flex flex-col h-screen sticky top-0 transition-all duration-300 ease-in-out overflow-hidden">

    <!-- Logo & Bouton de réduction -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 flex-shrink-0">
        <h1 x-show="sidebarOpen" x-transition class="text-xl font-bold text-primary tracking-tight whitespace-nowrap">
            Simple School
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

        @elseif(method_exists(auth()->user(), 'isAccountant') && auth()->user()->isAccountant())
        <!-- ========================================== -->
        <!-- MENU PERSONNEL COMPTABLE (accès restreint)  -->
        <!-- ========================================== -->
        <a href="{{ route('app.enrollments.index') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('app.enrollments.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">📝</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Inscriptions</span>
        </a>
        <a href="{{ route('app.payments.index') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('app.payments.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="text-xl min-w-[24px] text-center">💳</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Paiements</span>
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
        <div x-data="{ open: {{ request()->routeIs('app.students.*') || request()->routeIs('app.attendances.*') || request()->routeIs('app.report-cards.*') || request()->routeIs('app.parents.*') ? 'true' : 'false' }} }" class="space-y-1">
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
                @if(auth()->user()->isSchoolAdmin())
                <a href="{{ route('app.parents.index') }}" title="Parents" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.parents.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Parents</a>
                @endif
                <a href="{{ route('app.attendances.index') }}" title="Présences" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.attendances.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Présences</a>
                <a href="{{ route('app.report-cards.index') }}" title="Bulletins" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.report-cards.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Bulletins</a>
                <a href="{{ route('app.end-of-year.index') }}" title="Fin d'année & Passage" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.end-of-year.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Fin d'année & Passage</a>
            </div>
        </div>

        @if(auth()->user()->isSchoolAdmin())
        <!-- Groupe : Ressources Humaines -->
        <div x-data="{ open: {{ request()->routeIs('app.teachers.*') || request()->routeIs('app.teacher-assignments.*') || request()->routeIs('app.accountants.*') ? 'true' : 'false' }} }" class="space-y-1">
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
                <a href="{{ route('app.accountants.index') }}" title="Personnel Comptable" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.accountants.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Comptables</a>
            </div>
        </div>

        <!-- ✅ GROUPE : RAPPORTS -->
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
                <a href="{{ route('app.report-cards.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.report-cards.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Bulletins de notes</a>
                <a href="{{ route('app.financial.unpaid_by_class') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.financial.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">États de scolarité</a>
                <a href="{{ route('app.students.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.students.index') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Listes de classe & Exports</a>
                <a href="{{ route('app.attendances.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.attendances.index') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Rapport Présences/Absences</a>
                <a href="{{ route('app.notifications.index') }}" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('app.notifications.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Notifications SMS</span>
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
                <a href="{{ route('canteen.rates.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.rates.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Configuration des Tarifs</a>
                <a href="{{ route('canteen.subscriptions.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.subscriptions.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Inscriptions des Élèves</a>
                <a href="{{ route('canteen.payments.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.payments.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Paiements Cantine</a>
                <a href="{{ route('canteen.reports.unpaid_by_class') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('canteen.reports.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Rapports Cantine</a>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- GROUPE : GOÛTER MATERNELLE                 -->
        <!-- ========================================== -->
        <div x-data="{ open: {{ request()->routeIs('gouter.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Goûter Maternelle"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">🍪</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Goûter Maternelle</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                <a href="{{ route('gouter.rates.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('gouter.rates.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Configuration des Tarifs</a>
                <a href="{{ route('gouter.subscriptions.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('gouter.subscriptions.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Inscriptions des Élèves</a>
                <a href="{{ route('gouter.payments.index') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('gouter.payments.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Paiements Goûter</a>
                <a href="{{ route('gouter.reports.unpaid_by_class') }}" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('gouter.reports.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Rapports Goûter</a>
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
                <a href="{{ route('app.settings.sms') }}" title="Configuration SMS" class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.settings.*') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">Configuration SMS</a>
            </div>
        </div>
        @endif

        <!-- ✅ Menu déroulant Communication (Admin) -->
        <div x-data="{ open: {{ request()->routeIs('app.messages.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Communication"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none {{ request()->routeIs('app.messages.*') ? 'bg-primary/10 text-primary font-semibold' : '' }}">
                <div class="flex items-center">
                    <span class="text-xl min-w-[24px] text-center">💬</span>
                    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Communication</span>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
                @if(auth()->user()->isSchoolAdmin())
                <a href="{{ route('app.messages.broadcast') }}" title="Ecole parents" 
                   class="block px-4 py-2 text-sm rounded-md transition {{ request()->routeIs('app.messages.broadcast') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    Ecole parents
                </a>
                @endif

                <a href="{{ route('app.messages.index') }}" title="Parents école" 
                   class="block px-4 py-2 text-sm rounded-md transition flex items-center justify-between {{ request()->routeIs('app.messages.index') ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50' }}">
                    <span>Parents école</span>
                    
                    @php
                    $currentSchoolId = session('current_school_id') ?? auth()->user()->school_id;
                    // Messages reçus des parents uniquement (exclut les diffusions envoyées par l'école elle-même)
                    $adminUnreadMessages = \App\Models\Message::where('school_id', $currentSchoolId)
                        ->receivedFromParents()
                        ->where('is_read', false)
                        ->count();
                    @endphp
                    
                    @if($adminUnreadMessages > 0)
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-sm">{{ $adminUnreadMessages }}</span>
                    @endif
                </a>
            </div>
        </div>

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
@endif
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