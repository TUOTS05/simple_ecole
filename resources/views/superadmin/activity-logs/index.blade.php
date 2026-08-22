@extends('layouts.app')

@section('title', 'Journaux d\'activité')
@section('page_title', 'Journaux d\'activité (Audit Log)')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Historique des actions</h2>
            <span class="text-sm text-gray-500">Dernières 100 actions</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $log->created_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $log->user_name }}</div>
                                <div class="text-xs text-gray-500 uppercase">{{ $log->user_role }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badge = match($log->action) {
                                        'created_school' => 'bg-blue-100 text-blue-800',
                                        'updated_school' => 'bg-blue-100 text-blue-800',
                                        'deleted_school' => 'bg-red-100 text-red-800',
                                        'created_contract' => 'bg-green-100 text-green-800',
                                        'renewed_contract' => 'bg-purple-100 text-purple-800',
                                        'approved_subscription' => 'bg-green-100 text-green-800',
                                        'rejected_subscription' => 'bg-red-100 text-red-800',
                                        'created_user' => 'bg-indigo-100 text-indigo-800',
                                        'updated_user' => 'bg-indigo-100 text-indigo-800',
                                        'deleted_user' => 'bg-red-100 text-red-800',
                                        'payment_created' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                    $label = match($log->action) {
                                        'created_school' => 'Création École',
                                        'updated_school' => 'Modification École',
                                        'deleted_school' => 'Suppression École',
                                        'created_contract' => 'Activation Contrat',
                                        'renewed_contract' => 'Renouvellement',
                                        'approved_subscription' => 'Abonnement Approuvé',
                                        'rejected_subscription' => 'Abonnement Refusé',
                                        'created_user' => 'Création Utilisateur',
                                        'updated_user' => 'Modification Utilisateur',
                                        'deleted_user' => 'Suppression Utilisateur',
                                        'payment_created' => 'Paiement',
                                        default => 'Action',
                                    };
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badge }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ $log->ip_address }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Aucune activité enregistrée pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection