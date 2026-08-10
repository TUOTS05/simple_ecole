@extends('layouts.app')

@section('title', 'Nouveau Tarif Cantine')
@section('page_title', 'Créer un Tarif de Cantine')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        {{-- ✅ CORRECTION ICI : enlever "app." --}}
        <form action="{{ route('canteen.rates.store') }}" method="POST">
            @csrf

            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classe *</label>
                    <select name="school_class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        <option value="">-- Choisir --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tarif Mensuel (FCFA) *</label>
                    <input type="number" name="monthly_rate" required min="0" step="100" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de Mois *</label>
                    <input type="number" name="months_count" required min="1" max="12" value="10"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mois de Début *</label>
                    <input type="month" name="start_month" required value="{{ date('Y-09') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mois de Fin *</label>
                    <input type="month" name="end_month" required value="{{ date('Y') + 1 }}-06"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                          placeholder="Ex: Tarif cantine CE1 - 10 mois"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                {{-- ✅ CORRECTION ICI AUSSI : enlever "app." --}}
                <a href="{{ route('canteen.rates.index', ['school_year_id' => $schoolYearId]) }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    💾 Créer le Tarif
                </button>
            </div>
        </form>
    </div>
</div>
@endsection