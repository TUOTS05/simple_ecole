@extends('layouts.app')

@section('title', 'Importer des élèves')
@section('page_title', 'Importer une liste d\'élèves')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Compte rendu du dernier import --}}
    @if($report = session('import_report'))
        <div class="bg-white rounded-xl shadow-sm border {{ $report['errors'] ? 'border-yellow-300' : 'border-green-300' }} p-6">
            <p class="text-lg font-semibold {{ $report['imported'] ? 'text-green-700' : 'text-gray-700' }}">
                ✅ {{ $report['imported'] }} élève(s) importé(s).
            </p>
            @if($report['errors'])
                <p class="mt-3 font-semibold text-yellow-800">⚠️ {{ count($report['errors']) }} ligne(s) non importée(s) :</p>
                <ul class="mt-2 text-sm text-gray-700 list-disc pl-6 space-y-1 max-h-64 overflow-y-auto">
                    @foreach($report['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <p class="mt-2 text-xs text-gray-500">Corrigez ces lignes dans le fichier et importez-le à nouveau : les élèves déjà importés sont reconnus et ignorés.</p>
            @endif
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Étape 1 : modèle --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">1. Préparez le fichier</h2>
        <p class="text-sm text-gray-600 mb-4">
            Téléchargez le modèle et remplissez une ligne par élève. Colonnes obligatoires : <strong>Nom</strong>, <strong>Prénom</strong>,
            <strong>Sexe</strong> (M ou F) et <strong>Date de naissance</strong> (JJ/MM/AAAA). La <strong>Classe</strong> doit porter le même nom
            que dans l'application ; les colonnes du tuteur sont facultatives (avec un e-mail, un compte parent est créé).
        </p>
        <a href="{{ route('app.students.import.template') }}" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">
            📗 Télécharger le modèle Excel
        </a>
    </div>

    {{-- Étape 2 : envoi --}}
    <form action="{{ route('app.students.import.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf
        <h2 class="text-lg font-semibold text-gray-800">2. Importez-le</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fichier (Excel ou CSV) *</label>
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="block w-full text-sm border border-gray-300 rounded-lg p-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Classe par défaut</label>
            <select name="default_class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">— Utiliser la colonne « Classe » du fichier —</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('default_class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Utilisée pour les lignes dont la colonne « Classe » est vide (pratique pour importer une classe à la fois).</p>
        </div>

        <label class="flex items-start gap-3 text-sm text-gray-700">
            <input type="checkbox" name="notify_guardians" value="1" class="mt-1" @checked(old('notify_guardians', true))>
            <span>Envoyer leurs identifiants par e-mail aux tuteurs dont l'adresse est renseignée</span>
        </label>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <a href="{{ route('app.students.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg font-semibold">Retour à la liste</a>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold">Importer</button>
        </div>
    </form>
</div>
@endsection
