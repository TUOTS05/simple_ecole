{{-- Menu du personnel de l'école (directeur, secrétaire, comptable, responsables, enseignant
     avec droits supplémentaires). Chaque entrée n'apparaît que si l'utilisateur a le droit
     correspondant (voir App\Support\Permissions) : le même menu sert à tous les rôles. --}}
@php
    // Crèche seule : pas de bulletins ni de matières. Crèche active (seule ou non) : menu Crèche.
    $isCrecheOnly = (bool) session('current_school')?->isCrecheOnly();
    $hasCreche = (bool) session('current_school')?->isCreche();
    // Les rapports « Listes de classe » et « Présences » ouvrent les mêmes pages qu'Élèves / Présences :
    // ?menu=rapports indique d'où l'on vient, pour garder le groupe Rapports ouvert (et pas Vie Scolaire).
    $fromReports = request('menu') === 'rapports';
    $subLink = fn (bool $active) => 'block px-4 py-2 text-sm rounded-md transition '.($active ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-600 hover:text-primary hover:bg-gray-50');
@endphp

@can('dashboard.view')
<a href="{{ route('app.dashboard') }}" title="Tableau de bord" class="flex items-center px-3 py-3 rounded-lg transition {{ request()->routeIs('app.dashboard') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
    <span class="text-xl min-w-[24px] text-center">📊</span>
    <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Tableau de bord</span>
</a>
@endcan

@canany(['enrollments.manage', 'payments.manage'])
<!-- Groupe : Scolarité & Finances -->
<div x-data="{ open: {{ request()->routeIs('app.enrollments.*') || request()->routeIs('app.payments.*') ? 'true' : 'false' }} }" class="space-y-1">
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
        @can('enrollments.manage')
        <a href="{{ route('app.enrollments.index') }}" title="Inscriptions" class="{{ $subLink(request()->routeIs('app.enrollments.*')) }}">Inscriptions</a>
        @endcan
        @can('payments.manage')
        <a href="{{ route('app.payments.index') }}" title="Paiements" class="{{ $subLink(request()->routeIs('app.payments.*')) }}">Paiements</a>
        @endcan
    </div>
</div>
@endcanany

@canany(['students.manage', 'parents.view', 'attendances.view', 'report_cards.manage'])
<!-- Groupe : Vie Scolaire -->
<div x-data="{ open: {{ ! $fromReports && (request()->routeIs('app.students.*') || request()->routeIs('app.attendances.*') || request()->routeIs('app.report-cards.*') || request()->routeIs('app.parents.*') || request()->routeIs('app.end-of-year.*')) ? 'true' : 'false' }} }" class="space-y-1">
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
        @can('students.manage')
        <a href="{{ route('app.students.index') }}" title="Liste des Élèves" class="{{ $subLink(! $fromReports && request()->routeIs('app.students.*')) }}">Élèves</a>
        @endcan
        @can('parents.view')
        <a href="{{ route('app.parents.index') }}" title="Parents" class="{{ $subLink(request()->routeIs('app.parents.*')) }}">Parents</a>
        @endcan
        @can('attendances.view')
        <a href="{{ route('app.attendances.index') }}" title="Présences" class="{{ $subLink(! $fromReports && request()->routeIs('app.attendances.*')) }}">Présences</a>
        @endcan
        @can('report_cards.manage')
            @unless($isCrecheOnly)
            <a href="{{ route('app.report-cards.index') }}" title="Bulletins" class="{{ $subLink(request()->routeIs('app.report-cards.*')) }}">Bulletins</a>
            @endunless
            <a href="{{ route('app.end-of-year.index') }}" title="Fin d'année & Passage" class="{{ $subLink(request()->routeIs('app.end-of-year.*')) }}">Fin d'année & Passage</a>
        @endcan
    </div>
</div>
@endcanany

@if($hasCreche)
@canany(['creche.view', 'creche.attendance', 'creche.manage', 'attendances.view', 'students.manage'])
<!-- Groupe : Crèche -->
<div x-data="{ open: {{ request()->routeIs('app.creche-attendances.*') || request()->routeIs('app.creche.*') ? 'true' : 'false' }} }" class="space-y-1">
    <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Crèche"
        class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
        <div class="flex items-center">
            <span class="text-xl min-w-[24px] text-center">🍼</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Crèche</span>
        </div>
        <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
        @canany(['creche.view', 'creche.journal', 'creche.manage'])
        <a href="{{ route('app.creche.journal') }}" title="Journal du groupe" class="{{ $subLink(request()->routeIs('app.creche.journal*')) }}">Journal du groupe</a>
        @endcanany
        @canany(['creche.attendance', 'creche.manage', 'attendances.view'])
        <a href="{{ route('app.creche.today') }}" title="Présences du jour" class="{{ $subLink(request()->routeIs('app.creche.today')) }}">Présences du jour</a>
        <a href="{{ route('app.creche.departure.index') }}" title="Départ enfant" class="{{ $subLink(request()->routeIs('app.creche.departure.*')) }}">Départ enfant</a>
        @endcanany
        @canany(['creche.view', 'creche.journal', 'creche.manage'])
        @php
            $pendingIncidents = auth()->user()->can('creche.incidents.validate')
                ? \App\Models\CareIncident::where('school_id', session('current_school_id'))->where('status', 'declared')->count()
                : 0;
        @endphp
        <a href="{{ route('app.creche.incidents.index') }}" title="Incidents" class="{{ $subLink(request()->routeIs('app.creche.incidents.*')) }} flex items-center justify-between">
            <span>Incidents</span>
            @if($pendingIncidents > 0)
            <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold">{{ $pendingIncidents }}</span>
            @endif
        </a>
        @endcanany
        @canany(['creche.view', 'creche.attendance', 'creche.manage', 'students.manage'])
        <a href="{{ route('app.creche.children.index') }}" title="Enfants et dossiers crèche" class="{{ $subLink(request()->routeIs('app.creche.children.*')) }}">Enfants</a>
        @endcanany
        @canany(['creche.attendance', 'creche.manage', 'attendances.view'])
        <a href="{{ route('app.creche-attendances.index') }}" title="Historique du pointage" class="{{ $subLink(request()->routeIs('app.creche-attendances.*')) }}">Historique du pointage</a>
        @endcanany
        @can('classes.manage')
        <a href="{{ route('app.classes.index', ['cycle' => 'creche']) }}" title="Groupes" class="{{ $subLink(false) }}">Groupes</a>
        @endcan
        @can('creche.manage')
        <a href="{{ route('app.creche.assignments.index') }}" title="Affectations du personnel" class="{{ $subLink(request()->routeIs('app.creche.assignments.*')) }}">Affectations</a>
        <a href="{{ route('app.creche.settings.edit') }}" title="Paramètres de la crèche" class="{{ $subLink(request()->routeIs('app.creche.settings.*')) }}">Paramètres</a>
        @endcan
    </div>
</div>
@endcanany
@endif

@can('staff.manage')
<!-- Groupe : Personnel -->
<div x-data="{ open: {{ request()->routeIs('app.staff.*') || request()->routeIs('app.teacher-assignments.*') ? 'true' : 'false' }} }" class="space-y-1">
    <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Personnel"
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
        <a href="{{ route('app.staff.index') }}" title="Personnel et droits" class="{{ $subLink(request()->routeIs('app.staff.*')) }}">Personnel & droits</a>
        <a href="{{ route('app.teacher-assignments.index') }}" title="Assignations" class="{{ $subLink(request()->routeIs('app.teacher-assignments.*')) }}">Assignations</a>
    </div>
</div>
@endcan

@can('financial.view')
<!-- ✅ GROUPE : RAPPORTS -->
{{-- Les élèves et présences sont déjà actifs dans "Vie Scolaire" :
     on ne les surligne pas ici pour éviter deux menus actifs en même temps. --}}
<div x-data="{ open: {{ $fromReports || request()->routeIs('app.financial.*') || request()->routeIs('app.notifications.*') ? 'true' : 'false' }} }" class="space-y-1">
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
        <a href="{{ route('app.financial.unpaid_by_class') }}" class="{{ $subLink(request()->routeIs('app.financial.*')) }}">États de scolarité</a>
        @can('students.manage')
        <a href="{{ route('app.students.index', ['menu' => 'rapports']) }}" class="{{ $subLink($fromReports && request()->routeIs('app.students.*')) }}">Listes de classe & Exports</a>
        @endcan
        @can('attendances.view')
        <a href="{{ route('app.attendances.index', ['menu' => 'rapports']) }}" class="{{ $subLink($fromReports && request()->routeIs('app.attendances.*')) }}">Rapport Présences/Absences</a>
        @endcan
        <a href="{{ route('app.notifications.index') }}" class="{{ $subLink(request()->routeIs('app.notifications.*')) }}">Notifications SMS</a>
    </div>
</div>
@endcan

@can('canteen.manage')
<!-- GROUPE : CANTINE SCOLAIRE -->
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
        <a href="{{ route('canteen.rates.index') }}" class="{{ $subLink(request()->routeIs('canteen.rates.*')) }}">Configuration des Tarifs</a>
        <a href="{{ route('canteen.subscriptions.index') }}" class="{{ $subLink(request()->routeIs('canteen.subscriptions.*')) }}">Inscriptions des Élèves</a>
        <a href="{{ route('canteen.payments.index') }}" class="{{ $subLink(request()->routeIs('canteen.payments.*')) }}">Paiements Cantine</a>
        <a href="{{ route('canteen.reports.unpaid_by_class') }}" class="{{ $subLink(request()->routeIs('canteen.reports.*')) }}">Rapports Cantine</a>
    </div>
</div>
@endcan

@can('gouter.manage')
<!-- GROUPE : GOÛTER MATERNELLE -->
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
        <a href="{{ route('gouter.rates.index') }}" class="{{ $subLink(request()->routeIs('gouter.rates.*')) }}">Configuration des Tarifs</a>
        <a href="{{ route('gouter.subscriptions.index') }}" class="{{ $subLink(request()->routeIs('gouter.subscriptions.*')) }}">Inscriptions des Élèves</a>
        <a href="{{ route('gouter.payments.index') }}" class="{{ $subLink(request()->routeIs('gouter.payments.*')) }}">Paiements Goûter</a>
        <a href="{{ route('gouter.reports.unpaid_by_class') }}" class="{{ $subLink(request()->routeIs('gouter.reports.*')) }}">Rapports Goûter</a>
    </div>
</div>
@endcan

@canany(['extras.manage', 'extras.billing', 'extras.canteen', 'extras.transport'])
<!-- GROUPE : EXTRAS (Services & prestations) -->
<div x-data="{ open: {{ request()->routeIs('extras.*') ? 'true' : 'false' }} }" class="space-y-1">
    <button @click="if(!sidebarOpen) sidebarOpen = true; open = !open" title="Extras"
        class="w-full flex items-center justify-between px-3 py-3 rounded-lg transition text-gray-700 hover:bg-gray-100 focus:outline-none">
        <div class="flex items-center">
            <span class="text-xl min-w-[24px] text-center">🧩</span>
            <span x-show="sidebarOpen" class="ml-3 font-medium whitespace-nowrap">Extras</span>
        </div>
        <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    <div x-show="open && sidebarOpen" x-transition class="pl-11 space-y-1">
        @can('extras.billing')
        <a href="{{ route('extras.dashboard') }}" class="{{ $subLink(request()->routeIs('extras.dashboard')) }}">Tableau de bord</a>
        @endcan
        @can('extras.manage')
        <a href="{{ route('extras.categories.index') }}" class="{{ $subLink(request()->routeIs('extras.categories.*')) }}">Catégories</a>
        <a href="{{ route('extras.catalogue.index') }}" class="{{ $subLink(request()->routeIs('extras.catalogue.*')) }}">Catalogue</a>
        @endcan
        @can('extras.billing')
        <a href="{{ route('extras.subscriptions.index') }}" class="{{ $subLink(request()->routeIs('extras.subscriptions.*')) }}">Inscriptions</a>
        @endcan
        @can('extras.canteen')
        <a href="{{ route('extras.attendances.index') }}" class="{{ $subLink(request()->routeIs('extras.attendances.index')) }}">Présences / Consommations</a>
        <a href="{{ route('extras.attendances.scan') }}" class="{{ $subLink(request()->routeIs('extras.attendances.scan')) }}">Pointage rapide (QR)</a>
        <a href="{{ route('extras.menus.index') }}" class="{{ $subLink(request()->routeIs('extras.menus.*')) }}">Menus Cantine</a>
        @endcan
        @can('extras.transport')
        <a href="{{ route('extras.transport.vehicles.index') }}" class="{{ $subLink(request()->routeIs('extras.transport.vehicles.*')) }}">Véhicules</a>
        <a href="{{ route('extras.transport.routes.index') }}" class="{{ $subLink(request()->routeIs('extras.transport.routes.*')) }}">Circuits</a>
        <a href="{{ route('extras.transport.assignments.index') }}" class="{{ $subLink(request()->routeIs('extras.transport.assignments.*')) }}">Affectations élèves</a>
        <a href="{{ route('extras.transport.tracking.index') }}" class="{{ $subLink(request()->routeIs('extras.transport.tracking.*')) }}">Suivi GPS</a>
        @endcan
        @can('extras.manage')
        <a href="{{ route('extras.stocks.index') }}" class="{{ $subLink(request()->routeIs('extras.stocks.*')) }}">Stock</a>
        @endcan
        @can('extras.billing')
        <a href="{{ route('extras.payments.index') }}" class="{{ $subLink(request()->routeIs('extras.payments.*')) }}">Paiements</a>
        <a href="{{ route('extras.invoices.consolidated') }}" class="{{ $subLink(request()->routeIs('extras.invoices.*')) }}">Facture consolidée</a>
        <a href="{{ route('extras.refunds.index') }}" class="{{ $subLink(request()->routeIs('extras.refunds.*')) }}">Remboursements</a>
        <a href="{{ route('extras.reports.unpaid') }}" class="{{ $subLink(request()->routeIs('extras.reports.*')) }}">Impayés</a>
        @endcan
    </div>
</div>
@endcanany

@canany(['settings.manage', 'classes.manage', 'subjects.manage'])
<!-- Groupe : Paramètres de l'école -->
<div x-data="{ open: {{ request()->routeIs('app.classes.*') || request()->routeIs('app.subjects.*') || request()->routeIs('app.school-years.*') || request()->routeIs('app.class-fees.*') || request()->routeIs('app.settings.*') ? 'true' : 'false' }} }" class="space-y-1">
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
        @can('settings.manage')
        <a href="{{ route('app.school-years.index') }}" title="Années Scolaires" class="{{ $subLink(request()->routeIs('app.school-years.*')) }}">Années</a>
        <a href="{{ route('app.class-fees.index') }}" title="Configuration des Frais" class="{{ $subLink(request()->routeIs('app.class-fees.*')) }}">Frais</a>
        @endcan
        @can('classes.manage')
        <a href="{{ route('app.classes.index') }}" title="Classes" class="{{ $subLink(request()->routeIs('app.classes.*')) }}">Classes</a>
        @endcan
        @can('subjects.manage')
            @unless($isCrecheOnly)
            <a href="{{ route('app.subjects.index') }}" title="Matières" class="{{ $subLink(request()->routeIs('app.subjects.*')) }}">Matières</a>
            @endunless
        @endcan
        @can('settings.manage')
        <a href="{{ route('app.settings.customization') }}" title="Personnalisation" class="{{ $subLink(request()->routeIs('app.settings.customization*')) }}">Personnalisation</a>
        <a href="{{ route('app.settings.sms') }}" title="Configuration SMS" class="{{ $subLink(request()->routeIs('app.settings.sms*')) }}">Configuration SMS</a>
        @endcan
    </div>
</div>
@endcanany

@can('messages.manage')
<!-- ✅ Menu déroulant Communication -->
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
        <a href="{{ route('app.messages.broadcast') }}" title="Ecole parents" class="{{ $subLink(request()->routeIs('app.messages.broadcast')) }}">
            Ecole parents
        </a>

        @php
            $currentSchoolId = session('current_school_id') ?? auth()->user()->school_id;
            // Messages reçus des parents uniquement (exclut les diffusions envoyées par l'école elle-même)
            $adminUnreadMessages = \App\Models\Message::where('school_id', $currentSchoolId)
                ->receivedFromParents()
                ->where('is_read', false)
                ->count();
        @endphp
        <a href="{{ route('app.messages.index') }}" title="Parents école" class="{{ $subLink(request()->routeIs('app.messages.index')) }} flex items-center justify-between">
            <span>Parents école</span>
            @if($adminUnreadMessages > 0)
            <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-sm">{{ $adminUnreadMessages }}</span>
            @endif
        </a>
    </div>
</div>
@endcan
