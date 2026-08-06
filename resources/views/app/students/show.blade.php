@extends('layouts.app')

@section('title', 'Détails de l\'Élève')
@section('page_title', 'Profil de l\'Élève')

@section('content')
<div class="max-w-5xl mx-auto">
    
    <!-- En-tête -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center">
            <a href="{{ route('app.students.index') }}" class="mr-4 p-2 rounded-full hover:bg-gray-200 transition">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ strtoupper($student->last_name) }} {{ $student->first_name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Matricule : <span class="font-mono font-semibold text-gray-700">{{ $student->matricule }}</span></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.students.edit', $student->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white text-sm font-semibold rounded-lg hover:bg-yellow-600 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Modifier
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Colonne Gauche : Photo et Statut -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <div class="w-32 h-32 mx-auto rounded-full bg-gray-200 flex items-center justify-center mb-4 overflow-hidden border-4 border-white shadow-md">
                    @if($student->photo)
                        <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo" class="w-full h-full object-cover">
                    @else
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    @endif
                </div>
                <h2 class="text-xl font-bold text-gray-900">{{ $student->first_name }} {{ $student->last_name }}</h2>
                <p class="text-sm text-gray-500 mb-4">{{ $student->gender === 'M' ? 'Masculin' : 'Féminin' }} • Né(e) le {{ $student->birth_date ? $student->birth_date->format('d/m/Y') : 'N/A' }}</p>
                
                @if($student->status === 'active')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span> Actif
                    </span>
                @elseif($student->status === 'suspended')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span> Suspendu
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span> Inactif
                    </span>
                @endif
            </div>

            <!-- Informations Scolaires Rapides -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Scolarité</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Classe actuelle</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $student->classes->isNotEmpty() ? $student->classes->first()->name : 'Non assignée' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Section</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $student->section ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Famille nombreuse</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $student->large_family ? 'Oui' : 'Non' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Enfant du personnel</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $student->staff_child ? 'Oui' : 'Non' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Détails -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Coordonnées des Parents / Tuteur -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Contacts Familiaux
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Père -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Père</h4>
                        <p class="text-sm text-gray-900 font-medium">{{ $student->father_name ?: 'Non renseigné' }}</p>
                        <p class="text-sm text-gray-600">{{ $student->father_phone ?: 'Pas de téléphone' }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $student->father_occupation ?: '' }}</p>
                    </div>
                    <!-- Mère -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Mère</h4>
                        <p class="text-sm text-gray-900 font-medium">{{ $student->mother_name ?: 'Non renseigné' }}</p>
                        <p class="text-sm text-gray-600">{{ $student->mother_phone ?: 'Pas de téléphone' }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $student->mother_occupation ?: '' }}</p>
                    </div>
                    <!-- Tuteur -->
                    <div class="md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-100">
                        <h4 class="font-semibold text-blue-800 mb-2">Tuteur Légal (Contact principal)</h4>
                        <p class="text-sm text-gray-900 font-medium">{{ $student->guardian_name }}</p>
                        <p class="text-sm text-gray-600">📞 {{ $student->guardian_phone }}</p>
                        <p class="text-sm text-gray-600">✉️ {{ $student->guardian_email ?: 'Pas d\'email' }}</p>
                        <p class="text-xs text-gray-500 mt-1">Relation : {{ ucfirst($student->guardian_relation ?? 'Non spécifiée') }} | Profession : {{ $student->guardian_occupation ?? 'Non spécifiée' }}</p>
                    </div>
                </div>
            </div>

            <!-- Adresses et Divers -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Adresses & Informations
                </h3>
                <div class="space-y-4">
                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase">Adresse Actuelle</span>
                        <p class="text-sm text-gray-900 mt-1">{{ $student->current_address ?: 'Non renseignée' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase">Adresse Permanente</span>
                        <p class="text-sm text-gray-900 mt-1">{{ $student->permanent_address ?: 'Non renseignée' }}</p>
                    </div>
                    @if($student->previous_school || $student->remarks)
                        <div class="pt-4 border-t border-gray-100">
                            @if($student->previous_school)
                                <p class="text-sm text-gray-600"><span class="font-semibold">École précédente :</span> {{ $student->previous_school }}</p>
                            @endif
                            @if($student->remarks)
                                <p class="text-sm text-gray-600 mt-2"><span class="font-semibold">Remarques :</span> {{ $student->remarks }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Section Documents Officiels -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-6">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mr-3 text-sm font-bold">📄</span>
                        Documents Officiels de l'Élève
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Carte Scolaire -->
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg bg-blue-50">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                                    🪪
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Carte Scolaire</p>
                                    <p class="text-xs text-gray-500">Avec QR Code d'identification</p>
                                </div>
                            </div>
                            @if($student->id_card_path)
                                <a href="{{ asset('storage/' . $student->id_card_path) }}" target="_blank" 
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                                    Télécharger
                                </a>
                            @else
                                <span class="text-xs text-gray-400 italic">Non générée (Payer les frais d'inscription)</span>
                            @endif
                        </div>

                        <!-- Dernier Reçu de Paiement -->
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg bg-green-50">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                                    🧾
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Dernier Reçu</p>
                                    <p class="text-xs text-gray-500">Preuve de paiement officielle</p>
                                </div>
                            </div>
                            @php
                                $lastPayment = $student->enrollments->first()?->payments()->latest()->first();
                            @endphp
                            @if($lastPayment && $lastPayment->receipt_path)
                                <a href="{{ asset('storage/' . $lastPayment->receipt_path) }}" target="_blank" 
                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                                    Télécharger
                                </a>
                            @else
                                <span class="text-xs text-gray-400 italic">Aucun paiement enregistré</span>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection