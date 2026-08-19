@extends('layouts.app')

@section('title', 'Inscriptions Goûter')
@section('page_title', 'Inscriptions au Goûter (Maternelle)')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="gouterSubscriptionForm()">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">+ Inscrire des Élèves au Goûter</h3>

        @if($rates->isEmpty())
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
            ⚠️ Aucun tarif de goûter n'est encore configuré pour cette année scolaire. <a href="{{ route('gouter.rates.create', ['school_year_id' => $schoolYearId]) }}" class="underline font-semibold">Créez un tarif</a> avant d'inscrire des élèves.
        </div>
        @else
        <form action="{{ route('gouter.subscriptions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classe (Maternelle) *</label>
                    <select x-model="selectedClassId" @change="fetchStudents" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                        <option value="">-- Choisir une classe --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <p class="text-sm text-gray-500" x-show="selectedClassId">
                        💡 Tarif annuel de cette classe : <span class="font-bold text-primary" x-text="formatMoney(currentRateAmount)"></span> FCFA
                    </p>
                </div>
            </div>

            <div x-show="loading" class="text-center py-8 text-gray-500">
                <svg class="animate-spin h-8 w-8 mx-auto text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2">Chargement...</p>
            </div>

            <div x-show="!loading && students.length > 0" class="border border-gray-200 rounded-lg overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h4 class="font-semibold text-gray-700">Liste des élèves (<span x-text="students.length"></span>)</h4>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="student in students" :key="student.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 w-10">
                                        <input type="checkbox"
                                            :name="`students[${student.id}][selected]`"
                                            value="1"
                                            x-model="selectedStudents[student.id]"
                                            @change="toggleStudentSelection(student)"
                                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-500" x-text="student.matricule"></td>
                                    <td class="py-3 px-4 font-medium text-gray-800">
                                        <span x-text="student.last_name"></span> <span x-text="student.first_name"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="!loading && selectedClassId && students.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                Aucun élève trouvé dans cette classe pour l'année scolaire en cours.
            </div>

            <div x-show="Object.values(selectedStudents).some(v => v)" class="space-y-4 mb-6">
                <h4 class="text-lg font-bold text-gray-800">⚙️ Configuration des inscriptions</h4>

                <template x-for="student in students.filter(s => selectedStudents[s.id])" :key="student.id">
                    <div class="border border-gray-300 rounded-lg p-4 bg-blue-50">
                        <h5 class="font-semibold text-gray-800 mb-3">
                            <span x-text="student.last_name"></span> <span x-text="student.first_name"></span>
                        </h5>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant à encaisser maintenant (FCFA)</label>
                                <input type="number"
                                    :name="`students[${student.id}][amount]`"
                                    x-model="studentConfig[student.id].amount"
                                    required min="0"
                                    class="w-full px-4 py-2 border border-primary rounded-lg focus:ring-2 focus:ring-primary font-bold text-primary">
                                <p class="text-xs text-gray-500 mt-1">
                                    💡 Total de l'abonnement : <span x-text="formatMoney(currentRateAmount)"></span> FCFA. Laisser à 0 pour inscrire sans encaisser tout de suite.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                                <select :name="`students[${student.id}][payment_method]`"
                                    x-model="studentConfig[student.id].payment_method"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                                    <option value="cash">Espèces</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="transfer">Virement</option>
                                    <option value="check">Chèque</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    :disabled="loading || !Object.values(selectedStudents).some(v => v)"
                    class="bg-primary hover:bg-primary-dark disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-8 py-3 rounded-lg font-semibold transition">
                    ✅ Valider les inscriptions
                </button>
            </div>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Inscriptions actuelles</h3>

        <form method="GET" action="{{ route('gouter.subscriptions.index') }}" class="flex flex-col md:flex-row gap-4 items-end mb-6">
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

        <div class="overflow-x-auto">
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
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $sub->gouterRate->schoolClass->name ?? 'N/A' }}</td>
                        <td class="py-3 px-4 text-right text-gray-800">{{ number_format($sub->total_amount, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-right text-green-700 font-semibold">{{ number_format($sub->paid_amount, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-right text-red-700 font-semibold">{{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-center">
                            <span class="bg-{{ $sub->status === 'active' ? 'green' : 'gray' }}-100 text-{{ $sub->status === 'active' ? 'green' : 'gray' }}-700 px-2 py-1 rounded-full text-xs font-bold">
                                {{ ucfirst($sub->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('gouter.subscriptions.destroy', $sub->id) }}" method="POST" class="inline" onsubmit="return confirm('Annuler cette inscription ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">🗑️ Annuler</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-500">
                            Aucun élève inscrit au goûter pour cette année.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function gouterSubscriptionForm() {
        return {
            selectedSchoolYearId: '{{ $schoolYearId }}',
            selectedClassId: '',
            students: [],
            selectedStudents: {},
            studentConfig: {},
            loading: false,
            rates: {{ $rates->mapWithKeys(fn($r) => [$r->school_class_id => (float) $r->total_amount])->toJson() }},

            get currentRateAmount() {
                return this.rates[this.selectedClassId] ?? 0;
            },

            async fetchStudents() {
                this.students = []; this.selectedStudents = {}; this.studentConfig = {};
                if (!this.selectedClassId) return;
                this.loading = true;
                try {
                    const response = await fetch(`/gouter/students-by-class?class_id=${this.selectedClassId}&school_year_id=${this.selectedSchoolYearId}`);
                    this.students = await response.json();
                } catch (error) { console.error('Erreur:', error); } finally { this.loading = false; }
            },

            toggleStudentSelection(student) {
                if (this.selectedStudents[student.id]) {
                    this.studentConfig[student.id] = {
                        amount: 0,
                        payment_method: 'cash'
                    };
                } else {
                    delete this.studentConfig[student.id];
                }
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('fr-FR').format(amount);
            }
        }
    }
</script>
@endpush
@endsection
