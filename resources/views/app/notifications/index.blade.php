@extends('layouts.app')

@section('title', 'Historique des Notifications')
@section('page_title', 'Historique des Notifications')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <!-- En-tête avec stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">Envoyés</p>
            <p class="text-3xl font-bold text-gray-800">{{ $notifications->where('status', 'sent')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <p class="text-sm text-gray-600 mb-1">Échoués</p>
            <p class="text-3xl font-bold text-gray-800">{{ $notifications->where('status', 'failed')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">SMS</p>
            <p class="text-3xl font-bold text-gray-800">{{ $notifications->where('type', 'sms')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-sm text-gray-600 mb-1">Total</p>
            <p class="text-3xl font-bold text-gray-800">{{ $notifications->total() }}</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Nom élève, téléphone..."
                       class="w-full rounded-md border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full rounded-md border-gray-300">
                    <option value="">Tous</option>
                    <option value="sms" {{ request('type') == 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="email" {{ request('type') == 'email' ? 'selected' : '' }}>Email</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select name="category" class="w-full rounded-md border-gray-300">
                    <option value="">Toutes</option>
                    <option value="late_payment" {{ request('category') == 'late_payment' ? 'selected' : '' }}>Paiement en retard</option>
                    <option value="absence" {{ request('category') == 'absence' ? 'selected' : '' }}>Absence</option>
                    <option value="general" {{ request('category') == 'general' ? 'selected' : '' }}>Général</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" class="w-full rounded-md border-gray-300">
                    <option value="">Tous</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Envoyés</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Échoués</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark">
                    Filtrer
                </button>
                <a href="{{ route('app.notifications.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destinataire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($notifications as $notification)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $notification->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($notification->student)
                            {{ $notification->student->first_name }} {{ $notification->student->last_name }}
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $notification->recipient_phone ?? $notification->recipient_email }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ strtoupper($notification->type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                        <div class="truncate" title="{{ $notification->message }}">
                            {{ $notification->message }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($notification->status === 'sent')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                ✅ Envoyé
                            </span>
                        @elseif($notification->status === 'failed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" 
                                  title="{{ $notification->error_message }}">
                                ❌ Échoué
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                ⏳ En attente
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="mt-2">Aucune notification envoyée pour le moment.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection