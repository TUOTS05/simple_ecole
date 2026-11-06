@extends('layouts.app')

@section('title', 'Règlement intérieur')
@section('page_title', 'Règlement intérieur')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    @if($current)
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-primary">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Version en vigueur : v{{ $current->version }} — publiée le {{ $current->created_at->format('d/m/Y') }}</p>
                <h3 class="text-xl font-bold text-gray-800">{{ $current->title }}</h3>
            </div>
            @if($current->requires_acceptance)
            <a href="{{ route('app.regulations.show', $current) }}" class="text-center">
                <div class="text-3xl font-bold text-primary">{{ $current->acceptances_count }} / {{ $parentsCount }}</div>
                <div class="text-sm text-gray-600 underline">parents ont accepté</div>
            </a>
            @else
            <span class="text-sm text-gray-500">Consultation seule (acceptation non demandée)</span>
            @endif
        </div>
        @if($current->content)
        <div class="mt-4 max-h-64 overflow-y-auto text-sm text-gray-700 bg-gray-50 rounded-lg p-4 whitespace-pre-line">{{ $current->content }}</div>
        @endif
        @if($current->file_path)
        <a href="{{ route('app.regulations.download', $current) }}" class="inline-block mt-3 text-primary-dark underline text-sm">📎 Télécharger le PDF joint</a>
        @endif
    </div>
    @else
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 text-yellow-800 rounded">
        Aucun règlement intérieur publié pour l'instant. Rédigez-le ci-dessous : vos parents le verront dans leur espace.
    </div>
    @endif

    <form action="{{ route('app.regulations.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        @csrf
        <h3 class="text-lg font-bold text-gray-800">{{ $current ? '✏️ Publier une nouvelle version' : '✏️ Rédiger le règlement' }}</h3>
        @if($current)
        <p class="text-sm text-gray-500">Le texte est pré-rempli avec la version en vigueur. Publier crée la version v{{ $current->version + 1 }} ; si l'acceptation est demandée, tous les parents devront l'accepter à nouveau.</p>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
            <input type="text" name="title" value="{{ old('title', $current->title ?? 'Règlement intérieur') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Texte du règlement</label>
            <textarea name="content" rows="14" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                placeholder="Article 1 — Horaires&#10;Les cours commencent à 7h30…">{{ old('content', $current->content ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Et/ou document PDF (5 Mo max)</label>
            <input type="file" name="file" accept="application/pdf" class="text-sm">
        </div>
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" name="requires_acceptance" value="1" {{ old('requires_acceptance', $current->requires_acceptance ?? true) ? 'checked' : '' }} class="w-5 h-5 text-primary rounded focus:ring-primary">
            <span class="ml-3 text-gray-700">Demander aux parents d'accepter le règlement</span>
        </label>
        <div class="flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">Publier</button>
        </div>
    </form>

    @if($versions->count() > 1)
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <h3 class="text-lg font-bold text-gray-800 p-6 pb-2">Historique des versions</h3>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Version</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Titre</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Publiée le</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Par</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Acceptations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($versions as $version)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 font-semibold">v{{ $version->version }}</td>
                    <td class="py-3 px-4">{{ $version->title }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $version->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $version->publisher?->full_name ?? '—' }}</td>
                    <td class="py-3 px-4"><a href="{{ route('app.regulations.show', $version) }}" class="text-primary-dark underline">{{ $version->acceptances_count }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
