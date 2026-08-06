@extends('layouts.app')

@section('title', 'Configuration SMS')
@section('page_title', 'Configuration SMS (Orange SMS API)')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('app.settings.sms.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Activation -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-primary">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">🔔</span> Activation du service
            </h3>
            <label class="flex items-center cursor-pointer">
                <input type="hidden" name="sms_enabled" value="0">
                <input type="checkbox" name="sms_enabled" value="1" 
                    {{ $school->sms_enabled ? 'checked' : '' }}
                    class="w-5 h-5 text-primary rounded focus:ring-primary">
                <span class="ml-3 font-medium text-gray-700">Activer l'envoi automatique de SMS aux parents</span>
            </label>
        </div>

        <!-- Clés API -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">🔑</span> Identifiants Orange SMS API
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL de l'API</label>
                    <input type="url" name="orange_sms_api_url" value="{{ $school->orange_sms_api_url ?? 'https://api.orange.com/sms/v1' }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom expéditeur (max 11 car.)</label>
                    <input type="text" name="orange_sms_sender_name" value="{{ $school->orange_sms_sender_name ?? '' }}"
                        placeholder="ECOLETUO" maxlength="11"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                    <input type="text" name="orange_sms_client_id" value="{{ $clientId }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary font-mono text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                    <input type="password" name="orange_sms_client_secret" value="{{ $clientSecret }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary font-mono text-sm">
                </div>
            </div>
            <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-3 text-sm text-yellow-800">
                🔒 Les clés sont chiffrées avant d'être stockées en base de données.
            </div>
        </div>

        <!-- Template -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">📝</span> Template du message d'absence
            </h3>
            <textarea name="sms_absence_template" rows="4" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary font-mono text-sm">{{ $school->sms_absence_template ?? 'Cher(e) parent, nous vous informons que votre enfant {student_name} ({class_name}) a été absent(e) le {date}. Merci de contacter l\'établissement. {school_name}' }}</textarea>
            
            <div class="mt-3 text-xs text-gray-600">
                <p class="font-semibold mb-2">Variables disponibles :</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <code class="bg-gray-100 px-2 py-1 rounded">{parent_name}</code>
                    <code class="bg-gray-100 px-2 py-1 rounded">{student_name}</code>
                    <code class="bg-gray-100 px-2 py-1 rounded">{class_name}</code>
                    <code class="bg-gray-100 px-2 py-1 rounded">{date}</code>
                    <code class="bg-gray-100 px-2 py-1 rounded">{school_name}</code>
                    <code class="bg-gray-100 px-2 py-1 rounded">{school_phone}</code>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-bold shadow-lg transition">
                💾 Enregistrer la configuration
            </button>
        </div>
    </form>

    <!-- Historique -->
    <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="mr-2">📊</span> Derniers SMS envoyés
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Destinataire</th>
                        <th class="px-3 py-2 text-left">Message</th>
                        <th class="px-3 py-2 text-center">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentSms as $sms)
                    <tr>
                        <td class="px-3 py-2 text-xs">{{ $sms->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-2">{{ $sms->recipient_phone }}</td>
                        <td class="px-3 py-2 text-xs text-gray-600">{{ Str::limit($sms->message, 60) }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($sms->status === 'sent')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">✓ Envoyé</span>
                            @elseif($sms->status === 'failed')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs" title="{{ $sms->error_message }}">✗ Échec</span>
                            @else
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs">{{ $sms->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">Aucun SMS envoyé pour le moment.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection