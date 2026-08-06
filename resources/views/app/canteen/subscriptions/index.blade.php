@extends('layouts.app')

@section('title', 'Inscriptions Cantine')
@section('page_title', 'Inscriptions à la Cantine')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Formulaire d'Inscription -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">+ Inscrire un Élève à la Cantine</h3>
        <form action="{{ route('app.canteen.subscriptions.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Élève *</label>
                <select name="student_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="">-- Choisir un élève --</option>
                    @foreach(\App\Models\Student::where('school_id', session('current_school_id'))->orderBy('last_name')->get() as $student)
                        <option value="{{ $student->id }}">{{ $student->last_name }} {{ $student->first_name }} ({{ $student->matricule }})</option>
                    @endforeach
                </select>
                @error('student_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tarif *</label>
                <select name="canteen_rate_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="">-- Choisir --</option>
                    @foreach($rates as $rate)
                        <option value="{{ $rate->id }}">
                            {{ $rate->schoolClass->name }} - {{ number_format($rate->monthly_rate, 0, ',', ' ') }} FCFA/mois
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    ✅ Inscrire
                </button>
            </div>
        </form>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" action="{{ route('app.canteen.subscriptions.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année Scolaire</label>
                <select name="school_year_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" {{ $schoolYearId == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
                <select name="class_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="">Toutes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                🔍 Filtrer
            </button>
        </form>
    </div>

    <!-- Tableau des Inscriptions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Payé</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-red-600">Reste</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subscriptions as $sub)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 font-medium text-gray-800">
                        {{ $sub->student->last_name }} {{ $sub->student->first_name }}
                        <div class="text-xs text-gray-500 font-mono">{{ $sub->student->matricule }}</div>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $sub->canteenRate->schoolClass->name ?? 'N/A' }}</td>
                    <td class="py-3 px-4 text-right text-gray-800">{{ number_format($sub->total_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-right text-green-700 font-semibold">{{ number_format($sub->paid_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-right text-red-700 font-semibold">{{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-center">
                        <span class="bg-{{ $sub->status === 'active' ? 'green' : 'gray' }}-100 text-{{ $sub->status === 'active' ? 'green' : 'gray' }}-700 px-2 py-1 rounded-full text-xs font-bold">
                            {{ ucfirst($sub->status) }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <form action="{{ route('app.canteen.subscriptions.destroy', $sub->id) }}" method="POST" class="inline" onsubmit="return confirm('Annuler cette inscription ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">🗑️ Annuler</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">
                        Aucun élève inscrit à la cantine pour cette année.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection