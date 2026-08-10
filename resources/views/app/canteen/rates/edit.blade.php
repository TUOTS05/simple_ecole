@extends('layouts.app')

@section('title', 'Modifier Tarif Cantine')
@section('page_title', 'Modifier le Tarif')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        {{-- ✅ CORRECTION ICI : canteen.rates.update au lieu de app.canteen.rates.update --}}
        <form action="{{ route('canteen.rates.update', $rate->id) }}" method="POST">
            @csrf 
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classe *</label>
                    <select name="school_class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ $rate->school_class_id == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tarif Mensuel (FCFA) *</label>
                    <input type="number" name="monthly_rate" required min="0" step="100" value="{{ $rate->monthly_rate }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de Mois *</label>
                    <input type="number" name="months_count" required min="1" max="12" value="{{ $rate->months_count }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mois de Début *</label>
                    <input type="month" name="start_month" required value="{{ $rate->start_month }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mois de Fin *</label>
                    <input type="month" name="end_month" required value="{{ $rate->end_month }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">{{ $rate->description }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                {{-- ✅ CORRECTION ICI AUSSI : canteen.rates.index au lieu de app.canteen.rates.index --}}
                <a href="{{ route('canteen.rates.index', ['school_year_id' => $rate->school_year_id]) }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    💾 Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection