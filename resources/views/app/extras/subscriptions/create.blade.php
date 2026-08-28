@extends('layouts.app')

@section('title', 'Inscrire des Élèves')
@section('page_title', 'Inscrire des Élèves à un Extra')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="extraSubscriptionForm()">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    @if($extras->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 text-center text-gray-500">
        Aucun extra actif. <a href="{{ route('extras.catalogue.create') }}" class="text-primary underline">Créez-en un</a> d'abord.
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form action="{{ route('extras.subscriptions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">
            <input type="hidden" name="extra_id" x-bind:value="selectedExtraId">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Extra *</label>
                    <select x-model="selectedExtraId" @change="onExtraChange" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="">-- Choisir un extra --</option>
                        @foreach($extras as $extra)
                        <option value="{{ $extra->id }}" data-billing="{{ $extra->billing_type }}">{{ $extra->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cycle *</label>
                    <select x-model="selectedCycle" @change="fetchClasses" :disabled="!selectedExtraId" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white disabled:bg-gray-100">
                        <option value="">-- Choisir un cycle --</option>
                        <option value="maternelle">Maternelle</option>
                        <option value="primaire">Primaire</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classe *</label>
                    <select x-model="selectedClassId" @change="fetchStudentsAndTarif" :disabled="!selectedCycle" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white disabled:bg-gray-100">
                        <option value="">-- Choisir une classe --</option>
                        <template x-for="cls in classes" :key="cls.id">
                            <option :value="cls.id" x-text="cls.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div x-show="selectedClassId && !loading && !tarif" class="text-center py-6 text-orange-600 bg-orange-50 rounded-lg border border-dashed border-orange-300 mb-6">
                ⚠️ Aucun tarif défini pour cette classe. <a href="{{ route('extras.catalogue.index') }}" class="underline">Configurez un tarif</a> avant d'inscrire des élèves.
            </div>

            <div x-show="loading" class="text-center py-8 text-gray-500">
                <svg class="animate-spin h-8 w-8 mx-auto text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2">Chargement...</p>
            </div>

            <div x-show="!loading && tarif && students.length > 0" class="border border-gray-200 rounded-lg overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h4 class="font-semibold text-gray-700">Liste des élèves (<span x-text="students.length"></span>)</h4>
                    <span class="text-sm text-gray-500" x-show="tarif">Tarif : <span x-text="tarif ? tarif.amount : ''"></span> FCFA<span x-show="billingType === 'recurring'">/période</span></span>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="student in students" :key="student.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 w-10">
                                        <input type="checkbox"
                                            :name="`enrollments[${student.id}][selected]`"
                                            value="1"
                                            x-model="selectedStudents[student.id]"
                                            @change="toggleStudentSelection(student)"
                                            class="w-4 h-4 text-primary border-gray-300 rounded">
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

            <div x-show="!loading && selectedClassId && tarif && students.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300 mb-6">
                Aucun élève trouvé dans cette classe.
            </div>

            <div x-show="Object.values(selectedStudents).some(v => v)" class="space-y-4 mb-6">
                <h4 class="text-lg font-bold text-gray-800">⚙️ Configuration des inscriptions</h4>

                <template x-for="student in students.filter(s => selectedStudents[s.id])" :key="student.id">
                    <div class="border border-gray-300 rounded-lg p-4 bg-blue-50">
                        <h5 class="font-semibold text-gray-800 mb-3">
                            <span x-text="student.last_name"></span> <span x-text="student.first_name"></span>
                        </h5>

                        <template x-if="billingType === 'recurring'">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Périodes *</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                    <template x-for="period in availablePeriods" :key="period.value">
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox"
                                                :name="`enrollments[${student.id}][periods][]`"
                                                :value="period.value"
                                                x-model="studentConfig[student.id].periods"
                                                class="w-4 h-4 text-primary border-gray-300 rounded">
                                            <span class="text-sm" x-text="period.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="billingType === 'one_time'">
                            <input type="hidden" :name="`enrollments[${student.id}][periods][]`" value="unique">
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remise</label>
                                <select :name="`enrollments[${student.id}][discount_type]`" x-model="studentConfig[student.id].discount_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                                    <option value="">-- Aucune remise --</option>
                                    <option value="individual">Individuelle</option>
                                    <option value="family">Familiale</option>
                                    <option value="sibling">Fratrie</option>
                                    <option value="promotion">Promotion</option>
                                    <option value="scholarship">Bourse</option>
                                    <option value="exceptional">Exceptionnelle</option>
                                    <option value="free">Gratuité (100%)</option>
                                </select>
                            </div>
                            <div x-show="studentConfig[student.id].discount_type && studentConfig[student.id].discount_type !== 'free'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Taux de remise (%)</label>
                                <input type="number"
                                    :name="`enrollments[${student.id}][discount_percent]`"
                                    x-model="studentConfig[student.id].discount_percent"
                                    min="0" max="100"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                        <div x-show="studentConfig[student.id].discount_type" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Motif de la remise</label>
                            <input type="text"
                                :name="`enrollments[${student.id}][discount_reason]`"
                                x-model="studentConfig[student.id].discount_reason"
                                placeholder="Ex : 2ᵉ enfant inscrit à la cantine"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant à encaisser maintenant (FCFA)</label>
                                <input type="number"
                                    :name="`enrollments[${student.id}][amount]`"
                                    x-model="studentConfig[student.id].amount"
                                    min="0"
                                    class="w-full px-4 py-2 border border-primary rounded-lg font-bold text-primary">
                                <p class="text-xs text-gray-500 mt-1">
                                    💡 Total dû : <span x-text="studentTotal(student.id)"></span> FCFA
                                    <template x-if="studentConfig[student.id].discount_type">
                                        <span class="text-purple-600"> (remise appliquée)</span>
                                    </template>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                                <select :name="`enrollments[${student.id}][payment_method]`" x-model="studentConfig[student.id].payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                                    <option value="">-- Aucun paiement maintenant --</option>
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
    </div>
    @endif
</div>

@push('scripts')
<script>
    function extraSubscriptionForm() {
        return {
            selectedExtraId: '',
            selectedSchoolYearId: '{{ $schoolYearId }}',
            selectedCycle: '',
            selectedClassId: '',
            billingType: 'recurring',
            classes: [],
            students: [],
            selectedStudents: {},
            studentConfig: {},
            loading: false,
            tarif: null,
            availablePeriods: [],

            onExtraChange(event) {
                const option = event.target.selectedOptions[0];
                this.billingType = option ? (option.dataset.billing || 'recurring') : 'recurring';
                this.selectedCycle = ''; this.selectedClassId = ''; this.classes = [];
                this.students = []; this.selectedStudents = {}; this.studentConfig = {}; this.tarif = null;
            },

            async fetchClasses() {
                this.classes = []; this.students = []; this.selectedStudents = {}; this.studentConfig = {}; this.tarif = null;
                if (!this.selectedCycle) return;
                this.loading = true;
                try {
                    const response = await fetch(`/extras/classes-by-cycle?cycle=${encodeURIComponent(this.selectedCycle)}`);
                    this.classes = await response.json();
                } catch (error) { console.error(error); } finally { this.loading = false; }
            },

            async fetchStudentsAndTarif() {
                this.students = []; this.selectedStudents = {}; this.studentConfig = {}; this.tarif = null;
                if (!this.selectedClassId) return;
                this.loading = true;
                try {
                    const [studentsRes, tarifRes] = await Promise.all([
                        fetch(`/extras/students-by-class?class_id=${this.selectedClassId}&school_year_id=${this.selectedSchoolYearId}`),
                        fetch(`/extras/tarif-for-class?extra_id=${this.selectedExtraId}&school_year_id=${this.selectedSchoolYearId}&class_id=${this.selectedClassId}`),
                    ]);
                    this.students = await studentsRes.json();
                    this.tarif = await tarifRes.json();
                    this.billingType = this.tarif ? this.tarif.billing_type : this.billingType;
                    this.computeAvailablePeriods();
                } catch (error) { console.error(error); } finally { this.loading = false; }
            },

            computeAvailablePeriods() {
                this.availablePeriods = [];
                if (!this.tarif || this.billingType !== 'recurring') return;

                const monthLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                const addPeriod = (year, month) => {
                    const value = `${year}-${String(month).padStart(2, '0')}`;
                    this.availablePeriods.push({ value, label: `${monthLabels[month - 1]} ${year}` });
                };

                if (this.tarif.start_period && this.tarif.end_period) {
                    let [y, m] = this.tarif.start_period.split('-').map(Number);
                    const [endY, endM] = this.tarif.end_period.split('-').map(Number);
                    while (y < endY || (y === endY && m <= endM)) {
                        addPeriod(y, m);
                        m++; if (m > 12) { m = 1; y++; }
                    }
                } else {
                    const now = new Date();
                    let y = now.getFullYear(), m = now.getMonth() + 1;
                    const count = this.tarif.periods_count || 1;
                    for (let i = 0; i < count; i++) {
                        addPeriod(y, m);
                        m++; if (m > 12) { m = 1; y++; }
                    }
                }
            },

            toggleStudentSelection(student) {
                if (this.selectedStudents[student.id]) {
                    this.studentConfig[student.id] = {
                        periods: this.billingType === 'recurring' && this.availablePeriods.length ? [this.availablePeriods[0].value] : [],
                        amount: this.tarif ? this.tarif.amount : 0,
                        payment_method: '',
                        discount_type: '',
                        discount_percent: 0,
                        discount_reason: '',
                    };
                } else {
                    delete this.studentConfig[student.id];
                }
            },

            studentTotal(studentId) {
                if (!this.tarif) return 0;
                const config = this.studentConfig[studentId];
                if (!config) return 0;
                const gross = this.billingType === 'one_time'
                    ? this.tarif.amount
                    : (config.periods ? config.periods.length : 0) * this.tarif.amount;
                if (!config.discount_type) return gross;
                const percent = config.discount_type === 'free' ? 100 : (Number(config.discount_percent) || 0);
                return Math.round(gross * (1 - percent / 100));
            },
        };
    }
</script>
@endpush
@endsection
