@extends('layouts.app')

@section('title', 'Plans d\'abonnement')
@section('page_title', 'Plans d\'abonnement')

@section('content')
    <div class="max-w-7xl mx-auto">
        
        <!-- En-tête avec bouton créer -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Gestion des Plans</h2>
                <p class="text-gray-600 mt-1">Configurez les offres d'abonnement disponibles pour les écoles</p>
            </div>
            <a href="{{ route('superadmin.plans.create') }}" 
               class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition shadow-md">
                + Nouveau Plan
            </a>
        </div>

        <!-- Messages de succès/erreur -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Tableau des plans -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix mensuel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix annuel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Limites</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900">{{ $plan->name }}</div>
                                <div class="text-xs text-gray-500">{{ $plan->slug }}</div>
                                @if($plan->description)
                                    <div class="text-xs text-gray-600 mt-1">{{ Str::limit($plan->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-semibold">
                                {{ $plan->formatted_monthly_price }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-semibold">
                                {{ $plan->formatted_yearly_price }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div>👨‍🎓 {{ $plan->max_students }} élèves</div>
                                <div>👥 {{ $plan->max_users }} utilisateurs</div>
                                <div>🏫 {{ $plan->max_classes }} classes</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($plan->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actif</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <a href="{{ route('superadmin.plans.edit', $plan) }}" 
                                   class="text-blue-600 hover:text-blue-900">Modifier</a>
                                <form action="{{ route('superadmin.plans.destroy', $plan) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce plan ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Aucun plan d'abonnement créé. <a href="{{ route('superadmin.plans.create') }}" class="text-primary hover:underline">Créer le premier plan</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($plans->hasPages())
            <div class="mt-6">
                {{ $plans->links() }}
            </div>
        @endif

    </div>
@endsection