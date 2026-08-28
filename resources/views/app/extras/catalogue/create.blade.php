@extends('layouts.app')

@section('title', 'Nouvel Extra')
@section('page_title', 'Nouvel Extra')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ billingType: 'recurring' }">

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form action="{{ route('extras.catalogue.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie *</label>
                    <select name="extra_category_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('extra_category_id') == $category->id ? 'selected' : '' }}>{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                    <input type="text" name="code" required maxlength="30" value="{{ old('code') }}" placeholder="TRANSPORT-A" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du service *</label>
                <input type="text" name="name" required maxlength="150" value="{{ old('name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" maxlength="1000" class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de facturation *</label>
                    <select name="billing_type" x-model="billingType" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="recurring">Périodique (ex: mensuel)</option>
                        <option value="one_time">Frais unique</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacité max.</label>
                    <input type="number" name="capacity" min="1" value="{{ old('capacity') }}" placeholder="Illimitée" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Public concerné</label>
                    <input type="text" name="target_audience" maxlength="100" value="{{ old('target_audience') }}" placeholder="Tous, Maternelle..." class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Conditions d'accès</label>
                <textarea name="conditions" rows="2" maxlength="1000" class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('conditions') }}</textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('extras.catalogue.index') }}" class="px-6 py-2 rounded-lg font-semibold text-gray-600 hover:bg-gray-100 transition">Annuler</a>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Créer l'extra</button>
            </div>
        </form>
    </div>
</div>
@endsection
