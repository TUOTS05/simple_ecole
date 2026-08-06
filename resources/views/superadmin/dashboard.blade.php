@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page_title', 'Tableau de bord Super Admin')

@section('content')
<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 1 : CARTES DE STATISTIQUES PRINCIPALES --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        {{-- Total Écoles --}}
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase">Total Écoles</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalSchools }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <span class="text-green-600 font-semibold">{{ $activeSchools }}</span> actives
                    </p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <span class="text-3xl"></span>
                </div>
            </div>
        </div>

        {{-- Contrats Actifs --}}
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase">Contrats Actifs</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $activeContracts }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Sur {{ $totalContracts }} total
                    </p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <span class="text-3xl"></span>
                </div>
            </div>
        </div>

        {{-- Revenus Totaux --}}
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase">Revenus Totaux</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Ce mois : <span class="text-green-600 font-semibold">{{ number_format($monthlyRevenue, 0, ',', ' ') }} FCFA</span>
                    </p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <span class="text-3xl"></span>
                </div>
            </div>
        </div>

        {{-- Alertes --}}
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase">À Renouveler</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $expiringSoon->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Dans les 30 jours
                    </p>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <span class="text-3xl"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 2 : RÉPARTITION DES ÉCOLES PAR STATUT --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- Statut des écoles --}}
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2"></span> Répartition des écoles
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Actives</span>
                    <div class="flex items-center flex-1 ml-4">
                        <div class="flex-1 bg-gray-200 rounded-full h-3">
                            <div class="bg-green-500 h-3 rounded-full" style="width: {{ $totalSchools > 0 ? ($activeSchools / $totalSchools * 100) : 0 }}%"></div>
                        </div>
                        <span class="ml-3 text-sm font-bold text-gray-900">{{ $activeSchools }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Suspendues</span>
                    <div class="flex items-center flex-1 ml-4">
                        <div class="flex-1 bg-gray-200 rounded-full h-3">
                            <div class="bg-red-500 h-3 rounded-full" style="width: {{ $totalSchools > 0 ? ($suspendedSchools / $totalSchools * 100) : 0 }}%"></div>
                        </div>
                        <span class="ml-3 text-sm font-bold text-gray-900">{{ $suspendedSchools }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">En attente</span>
                    <div class="flex items-center flex-1 ml-4">
                        <div class="flex-1 bg-gray-200 rounded-full h-3">
                            <div class="bg-yellow-500 h-3 rounded-full" style="width: {{ $totalSchools > 0 ? ($pendingSchools / $totalSchools * 100) : 0 }}%"></div>
                        </div>
                        <span class="ml-3 text-sm font-bold text-gray-900">{{ $pendingSchools }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Statut des contrats --}}
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2"></span> État des contrats
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-green-50 p-4 rounded-lg border border-green-200 text-center">
                    <p class="text-3xl font-bold text-green-700">{{ $activeContracts }}</p>
                    <p class="text-xs text-green-600 uppercase font-semibold mt-1">Actifs</p>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 text-center">
                    <p class="text-3xl font-bold text-blue-700">{{ $renewedContracts }}</p>
                    <p class="text-xs text-blue-600 uppercase font-semibold mt-1">Renouvelés</p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg border border-red-200 text-center">
                    <p class="text-3xl font-bold text-red-700">{{ $expiredContracts }}</p>
                    <p class="text-xs text-red-600 uppercase font-semibold mt-1">Expirés</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-center">
                    <p class="text-3xl font-bold text-gray-700">{{ $totalContracts }}</p>
                    <p class="text-xs text-gray-600 uppercase font-semibold mt-1">Total</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 3 : ALERTES ET ACTIVITÉS RÉCENTES --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- Contrats expirant bientôt --}}
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-red-50">
                <h3 class="text-lg font-bold text-red-800 flex items-center">
                    <span class="mr-2">⚠️</span> Contrats à renouveler (30 jours)
                </h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($expiringSoon as $contract)
                    <div class="p-4 hover:bg-gray-50 flex justify-between items-center">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $contract->school->name ?? 'École inconnue' }}</p>
                            <p class="text-xs text-gray-500">{{ $contract->contract_number }} • {{ $contract->plan_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-red-600">
                                Expire le {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                            </p>
                            <a href="{{ route('superadmin.subscriptions.renew', $contract->id) }}" 
                               class="text-xs text-green-600 hover:underline font-semibold">
                                🔄 Renouveler
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                         Aucun contrat n'expire dans les 30 prochains jours.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Activités récentes --}}
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-blue-50">
                <h3 class="text-lg font-bold text-blue-800 flex items-center">
                    <span class="mr-2"></span> Activités récentes
                </h3>
            </div>
            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                @forelse($recentActivities as $activity)
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Par <span class="font-semibold">{{ $activity->user_name }}</span> 
                                    • <span class="uppercase">{{ $activity->user_role }}</span>
                                </p>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap ml-2">
                                {{ $activity->created_at }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        Aucune activité enregistrée.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 4 : DERNIÈRES ÉCOLES INSCRITES --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <span class="mr-2"></span> Dernières écoles inscrites
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscrite le</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentSchools as $school)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $school->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $school->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $school->subscription_plan ?? '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusClass = match($school->status) {
                                        'active' => 'bg-green-100 text-green-800',
                                        'suspended' => 'bg-red-100 text-red-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                    {{ ucfirst($school->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $school->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Aucune école enregistrée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection