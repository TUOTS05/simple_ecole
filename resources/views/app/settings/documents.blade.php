@extends('layouts.app')

@section('title', 'Personnalisation des documents')
@section('page_title', 'Personnalisation des documents')

@php
    $input = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary';
    $checkbox = 'w-5 h-5 text-primary rounded focus:ring-primary';
    $scale = old('report_card.scale', $settings['report_card']['scale']);
    // Deux lignes libres en plus pour ajouter des paliers sans JavaScript
    $scale = array_pad(array_values($scale), count($scale) + 2, ['min' => null, 'label' => null]);
@endphp

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap items-center gap-3">
        <span class="text-sm text-gray-600">Aperçu avec des données fictives (enregistrez d'abord vos modifications) :</span>
        <a href="{{ route('app.settings.documents.preview', 'report_card') }}" target="_blank" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 hover:bg-gray-200">📄 Bulletin</a>
        <a href="{{ route('app.settings.documents.preview', 'student_card') }}" target="_blank" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 hover:bg-gray-200">🪪 Carte scolaire</a>
        <a href="{{ route('app.settings.documents.preview', 'receipt') }}" target="_blank" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 hover:bg-gray-200">🧾 Reçu</a>
    </div>

    <form action="{{ route('app.settings.documents.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- En-tête officiel -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-primary">
            <h3 class="text-lg font-bold text-gray-800 mb-1">🏛️ En-tête officiel</h3>
            <p class="text-sm text-gray-500 mb-4">Affiché sur tous les documents, au-dessus du nom de l'école (nom, logo et coordonnées se modifient dans « Mon Profil »).</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lignes officielles (une par ligne)</label>
                    <textarea name="header[lines]" rows="3" class="{{ $input }}" placeholder="RÉPUBLIQUE DE CÔTE D'IVOIRE&#10;Ministère de l'Éducation Nationale">{{ old('header.lines', $settings['header']['lines']) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Devise</label>
                    <input type="text" name="header[motto]" value="{{ old('header.motto', $settings['header']['motto']) }}" class="{{ $input }}" placeholder="Union - Discipline - Travail">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville (« Fait à … »)</label>
                    <input type="text" name="header[city]" value="{{ old('header.city', $settings['header']['city']) }}" class="{{ $input }}" placeholder="Abidjan">
                </div>
            </div>
        </div>

        <!-- Identité visuelle & signature -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">🎨 Couleurs, cachet et signature</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Couleur principale</label>
                    <input type="color" name="branding[primary_color]" value="{{ old('branding.primary_color', $settings['branding']['primary_color']) }}" class="h-10 w-20 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Couleur secondaire</label>
                    <input type="color" name="branding[accent_color]" value="{{ old('branding.accent_color', $settings['branding']['accent_color']) }}" class="h-10 w-20 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre du signataire</label>
                    <input type="text" name="branding[signatory_title]" value="{{ old('branding.signatory_title', $settings['branding']['signatory_title']) }}" class="{{ $input }}" placeholder="Le Directeur">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du signataire</label>
                    <input type="text" name="branding[signatory_name]" value="{{ old('branding.signatory_name', $settings['branding']['signatory_name']) }}" class="{{ $input }}" placeholder="M. KOUASSI Jean">
                </div>
                @foreach(['stamp' => 'Cachet de l\'école', 'signature' => 'Signature scannée'] as $image => $label)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }} (PNG transparent conseillé, 1 Mo max)</label>
                    @if($settings['branding'][$image])
                        <div class="flex items-center gap-3 mb-2">
                            <img src="{{ asset('storage/' . $settings['branding'][$image]) }}" alt="{{ $label }}" class="h-16 border rounded bg-gray-50 p-1">
                            <label class="text-sm text-red-600 flex items-center gap-1">
                                <input type="checkbox" name="remove_{{ $image }}" value="1"> Retirer
                            </label>
                        </div>
                    @endif
                    <input type="file" name="{{ $image }}" accept="image/png,image/jpeg" class="text-sm">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Bulletin -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">📄 Bulletin de notes</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre du bulletin mensuel</label>
                    <input type="text" name="report_card[title_monthly]" value="{{ old('report_card.title_monthly', $settings['report_card']['title_monthly']) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre du bulletin trimestriel</label>
                    <input type="text" name="report_card[title_quarterly]" value="{{ old('report_card.title_quarterly', $settings['report_card']['title_quarterly']) }}" class="{{ $input }}" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre du cadre d'appréciation de l'enseignant</label>
                    <input type="text" name="report_card[teacher_box_title]" value="{{ old('report_card.teacher_box_title', $settings['report_card']['teacher_box_title']) }}" class="{{ $input }}" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note de bas de page</label>
                    <input type="text" name="report_card[footer_note]" value="{{ old('report_card.footer_note', $settings['report_card']['footer_note']) }}" class="{{ $input }}" placeholder="Bulletin à conserver précieusement, aucun duplicata ne sera délivré.">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach(['show_rank' => 'Afficher le rang', 'show_class_size' => 'Afficher l\'effectif de la classe', 'show_council_mention' => 'Afficher la mention du conseil', 'show_stamp' => 'Apposer le cachet et la signature'] as $key => $label)
                <label class="flex items-center cursor-pointer">
                    <input type="hidden" name="report_card[{{ $key }}]" value="0">
                    <input type="checkbox" name="report_card[{{ $key }}]" value="1" {{ old("report_card.$key", $settings['report_card'][$key]) ? 'checked' : '' }} class="{{ $checkbox }}">
                    <span class="ml-3 text-gray-700">{{ $label }}</span>
                </label>
                @endforeach
            </div>

            <h4 class="font-semibold text-gray-800 mt-6 mb-2">Barème des appréciations</h4>
            <p class="text-sm text-gray-500 mb-3">Appliqué à chaque matière (sauf observation saisie par l'enseignant) et à la moyenne générale. Note sur 20 ; laissez une ligne vide pour la supprimer.</p>
            <div class="space-y-2 max-w-md">
                @foreach($scale as $i => $step)
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 w-6">≥</span>
                    <input type="number" step="0.25" min="0" max="20" name="report_card[scale][{{ $i }}][min]" value="{{ $step['min'] }}" class="w-24 px-3 py-2 border border-gray-300 rounded-lg">
                    <input type="text" name="report_card[scale][{{ $i }}][label]" value="{{ $step['label'] }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" placeholder="Appréciation">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Carte scolaire -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">🪪 Carte scolaire</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Intitulé de la carte</label>
                    <input type="text" name="student_card[title]" value="{{ old('student_card.title', $settings['student_card']['title']) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mention en bas de carte</label>
                    <input type="text" name="student_card[footer_note]" value="{{ old('student_card.footer_note', $settings['student_card']['footer_note']) }}" class="{{ $input }}" placeholder="En cas de perte, merci de la rapporter à l'école">
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach(['show_birth_date' => 'Afficher la date de naissance', 'show_gender' => 'Afficher le sexe'] as $key => $label)
                <label class="flex items-center cursor-pointer">
                    <input type="hidden" name="student_card[{{ $key }}]" value="0">
                    <input type="checkbox" name="student_card[{{ $key }}]" value="1" {{ old("student_card.$key", $settings['student_card'][$key]) ? 'checked' : '' }} class="{{ $checkbox }}">
                    <span class="ml-3 text-gray-700">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-3">Les cartes déjà générées ne changent pas : utilisez « Réimprimer la carte » sur la fiche de l'élève.</p>
        </div>

        <!-- Reçu -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">🧾 Reçu de paiement</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
                    <input type="text" name="receipt[title]" value="{{ old('receipt.title', $settings['receipt']['title']) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Devise monétaire</label>
                    <input type="text" name="receipt[currency]" value="{{ old('receipt.currency', $settings['receipt']['currency']) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Libellé de la zone de signature</label>
                    <input type="text" name="receipt[signature_label]" value="{{ old('receipt.signature_label', $settings['receipt']['signature_label']) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message de bas de page</label>
                    <input type="text" name="receipt[footer_text]" value="{{ old('receipt.footer_text', $settings['receipt']['footer_text']) }}" class="{{ $input }}">
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach(['show_pending_installments' => 'Lister les échéances restantes', 'show_stamp' => 'Apposer le cachet et la signature'] as $key => $label)
                <label class="flex items-center cursor-pointer">
                    <input type="hidden" name="receipt[{{ $key }}]" value="0">
                    <input type="checkbox" name="receipt[{{ $key }}]" value="1" {{ old("receipt.$key", $settings['receipt'][$key]) ? 'checked' : '' }} class="{{ $checkbox }}">
                    <span class="ml-3 text-gray-700">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
