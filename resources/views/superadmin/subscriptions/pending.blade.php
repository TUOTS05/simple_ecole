@extends('layouts.app')

@section('title', 'Demandes d\'abonnement en attente')
@section('page_title', 'Demandes d\'abonnement en attente')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded flex items-center">
            <span class="mr-2">✅</span> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-6 py-4">École</th>
                        <th class="px-6 py-4">Plan demandé</th>
                        <th class="px-6 py-4">Durée</th>
                        <th class="px-6 py-4">Date de la demande</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $req)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $req->school->name }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                {{ $req->plan->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 capitalize">{{ $req->duration === 'yearly' ? 'Annuel' : 'Mensuel' }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 text-center space-x-2">
                            <!-- Bouton Approuver -->
                            <button onclick="document.getElementById('approve-modal-{{ $req->id }}').classList.remove('hidden')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                ✓ Approuver
                            </button>
                            
                            <!-- Bouton Refuser -->
                            <button onclick="document.getElementById('reject-modal-{{ $req->id }}').classList.remove('hidden')" 
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                ✗ Refuser
                            </button>
                        </td>
                    </tr>

                    <!-- Modal d'Approbation -->
                    <div id="approve-modal-{{ $req->id }}" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg p-6 w-full max-w-md">
                            <h3 class="text-lg font-bold mb-4">Approuver la demande de {{ $req->school->name }} ?</h3>
                            <form action="{{ route('superadmin.subscriptions.requests.approve', $req->id) }}" method="POST">
                                @csrf
                                <label class="block text-sm font-medium text-gray-700 mb-1">Note interne (optionnel)</label>
                                <textarea name="admin_notes" rows="3" class="w-full border-gray-300 rounded-lg mb-4" placeholder="Ex: Paiement reçu par virement..."></textarea>
                                <div class="flex justify-end space-x-3">
                                    <button type="button" onclick="document.getElementById('approve-modal-{{ $req->id }}').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Annuler</button>
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold">Confirmer et Générer le Contrat</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal de Refus -->
                    <div id="reject-modal-{{ $req->id }}" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg p-6 w-full max-w-md">
                            <h3 class="text-lg font-bold mb-4 text-red-600">Refuser la demande ?</h3>
                            <form action="{{ route('superadmin.subscriptions.requests.reject', $req->id) }}" method="POST">
                                @csrf
                                <label class="block text-sm font-medium text-gray-700 mb-1">Raison du refus (obligatoire)</label>
                                <textarea name="admin_notes" rows="3" class="w-full border-gray-300 rounded-lg mb-4" required placeholder="Ex: Capacité maximale d'élèves dépassée..."></textarea>
                                <div class="flex justify-end space-x-3">
                                    <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Annuler</button>
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold">Confirmer le refus</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            🎉 Aucune demande en attente pour le moment.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection