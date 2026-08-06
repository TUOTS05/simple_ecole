@extends('layouts.app')

@section('title', 'Configuration des Frais')
@section('page_title', 'Frais par Classe')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <!-- En-tête -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Configuration des Frais par Classe</h1>
        <p class="text-sm text-gray-500 mt-1">Définissez les montants de scolarité et les modalités de paiement pour chaque classe.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tableau des classes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Classe</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Scolarité Totale</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Frais d'Inscription</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Modalité</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Échéances</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Montant/Échéance</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($classes as $class)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ $class->name }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst($class->cycle ?? '') }} - {{ ucfirst($class->level ?? '') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">
                                    {{ number_format($class->total_tuition ?? 0, 0, ',', ' ') }} FCFA
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-700">
                                    {{ number_format($class->registration_fee ?? 0, 0, ',', ' ') }} FCFA
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $modalities = [
                                        'unique' => 'Paiement unique',
                                        'mensuel' => 'Mensuel',
                                        'trimestriel' => 'Trimestriel',
                                        'semestriel' => 'Semestriel'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    {{ $modalities[$class->payment_modality ?? 'unique'] ?? 'Non configuré' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $class->number_of_installments ?? 1 }} échéance(s)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-green-700">
                                    {{ number_format($class->installment_amount ?? 0, 0, ',', ' ') }} FCFA
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('app.class-fees.edit', $class->id) }}" 
                                   class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary-dark transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Configurer
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <p class="text-lg font-medium">Aucune classe configurée</p>
                                <p class="text-sm mt-1">Créez d'abord des classes dans la section "Classes".</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection