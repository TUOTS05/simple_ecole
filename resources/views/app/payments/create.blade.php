@extends('layouts.app')

@section('title', 'Nouveau Paiement')
@section('page_title', 'Nouveau Paiement')

@section('content')
    <div class="max-w-6xl mx-auto">
        
        <!-- En-tête -->
        <div class="bg-gradient-to-r from-primary to-primary-dark text-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">💰 Enregistrer un paiement</h1>
                    <p class="opacity-90">Suivez les étapes pour enregistrer un paiement</p>
                </div>
                <div class="text-right">
                    <p class="text-sm opacity-75">Date du jour</p>
                    <p class="text-2xl font-bold">{{ now()->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                <p class="font-bold">❌ Erreurs lors de l'enregistrement :</p>
                <ul class="list-disc list-inside mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form id="payment-form" action="{{ route('app.payments.store') }}" method="POST">
            @csrf
            
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 1 : IDENTIFICATION DE L'ÉLÈVE -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-primary">
                
                <div class="flex items-center mb-6">
                    <div class="bg-primary text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg mr-3">1</div>
                    <h2 class="text-2xl font-bold text-gray-800">Identification de l'élève</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Année scolaire -->
                    <div class="bg-blue-50 p-4 rounded-lg border-2 border-blue-200">
                        <label class="flex items-center text-sm font-semibold text-blue-900 mb-2">
                            <span class="mr-2">📅</span> Année scolaire *
                        </label>
                        <select name="school_year_id" id="school_year_id" required onchange="handleYearChange()"
                                class="w-full px-4 py-3 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                            <option value="">-- Sélectionner --</option>
                            @foreach($schoolYears as $year)
                                <option value="{{ $year->id }}" {{ old('school_year_id', $selectedYearId) == $year->id ? 'selected' : '' }}>
                                    {{ $year->name }} {{ $year->is_active ? '⭐' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Cycle -->
                    <div class="bg-purple-50 p-4 rounded-lg border-2 border-purple-200">
                        <label class="flex items-center text-sm font-semibold text-purple-900 mb-2">
                            <span class="mr-2">🎓</span> Cycle *
                        </label>
                        <select name="cycle" id="cycle" required onchange="filterClasses()"
                                class="w-full px-4 py-3 border-2 border-purple-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                            <option value="">-- Sélectionner --</option>
                            @foreach($cycles as $value => $label)
                                <option value="{{ $value }}" {{ old('cycle', $selectedCycle) === $value ? 'selected' : '' }}>
                                    {{ $value === 'maternelle' ? '🧒' : '📚' }} {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Classe -->
                    <div class="bg-green-50 p-4 rounded-lg border-2 border-green-200">
                        <label class="flex items-center text-sm font-semibold text-green-900 mb-2">
                            <span class="mr-2">🏫</span> Classe *
                        </label>
                        <select name="class_id" id="class_id" required onchange="loadStudentsByClass()"
                                class="w-full px-4 py-3 border-2 border-green-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                            <option value="">-- D'abord, sélectionner un cycle --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" data-cycle="{{ $class->cycle }}" {{ old('class_id', $selectedClassId) == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- Tableau dynamique des élèves -->
                <div class="mt-6">
                    <input type="hidden" name="student_id" id="selected_student_id">
                    <input type="hidden" name="enrollment_id" id="selected_enrollment_id">
                    <input type="hidden" name="student_installment_id" id="selected_installment_id">
                    
                    <div id="students-table-container" class="hidden bg-white border-2 border-yellow-200 rounded-lg overflow-hidden shadow-sm">
                        <div class="bg-yellow-50 px-4 py-3 border-b border-yellow-200">
                            <h4 class="font-bold text-yellow-900 flex items-center">
                                <span class="mr-2">👨‍🎓</span> Cochez l'élève pour lequel vous souhaitez enregistrer un paiement
                            </h4>
                        </div>
                        <div class="overflow-x-auto max-h-64 overflow-y-auto">
                            <table class="min-w-full divide-y divide-yellow-100">
                                <thead class="bg-yellow-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-16">Sélection</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Matricule</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Nom et Prénom</th>
                                    </tr>
                                </thead>
                                <tbody id="students-table-body" class="bg-white divide-y divide-gray-100">
                                    <!-- Rempli par JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Conteneur dynamique des échéances -->
                <div id="installments-container" class="hidden mt-6"></div>
                
            </div>
            
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 2 : DÉTAILS DU PAIEMENT -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-accent">
                <div class="flex items-center mb-6">
                    <div class="bg-accent text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg mr-3">2</div>
                    <h2 class="text-2xl font-bold text-gray-800">Détails du paiement</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Type de paiement (déduit automatiquement) -->
                    <div>
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                            <span class="mr-2">💳</span> Type de paiement
                        </label>
                        <input type="text" id="payment_type_display" readonly
                               value="-- Sélectionnez une échéance --"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg bg-gray-100 text-gray-700 font-semibold cursor-not-allowed">
                        <input type="hidden" name="payment_type" id="payment_type">
                    </div>
                    
                    <!-- Montant -->
                    <div>
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                            <span class="mr-2">💰</span> Montant à payer (FCFA) *
                        </label>
                        <input type="number" name="amount" id="amount_to_pay" value="{{ old('amount') }}" required min="0" step="100"
                               placeholder="Se remplit automatiquement"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-lg font-bold bg-gray-50">
                        <p class="text-xs text-gray-500 mt-1">Ce montant sera vérifié par rapport au reste dû de l'échéance.</p>
                    </div>
                    
                    <!-- Date de paiement -->
                    <div>
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                            <span class="mr-2">📆</span> Date de paiement *
                        </label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    
                    <!-- Méthode de paiement -->
                    <div>
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                            <span class="mr-2">🏦</span> Méthode de paiement *
                        </label>
                        <select name="payment_method" required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">-- Sélectionner --</option>
                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>💵 Espèces</option>
                            <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>📄 Chèque</option>
                            <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>🏦 Virement bancaire</option>
                            <option value="mobile_money" {{ old('payment_method') == 'mobile_money' ? 'selected' : '' }}>📱 Mobile Money</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 3 : INFORMATIONS COMPLÉMENTAIRES -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-secondary">
                <div class="flex items-center mb-6">
                    <div class="bg-secondary text-gray-800 rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg mr-3">3</div>
                    <h2 class="text-2xl font-bold text-gray-800">Informations complémentaires</h2>
                    <span class="ml-3 text-sm text-gray-500">(Optionnel)</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                            <span class="mr-2">🔖</span> Référence
                        </label>
                        <input type="text" name="reference" value="{{ old('reference') }}" 
                               placeholder="Numéro de reçu, numéro de transaction..."
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                            <span class="mr-2">📝</span> Notes
                        </label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder="Observations optionnelles...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- BOUTONS D'ACTION -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-gray-400">
                <div class="flex justify-between items-center">
                    <a href="{{ route('app.payments.index') }}" 
                       class="flex items-center text-gray-600 hover:text-gray-800 font-semibold transition">
                        <span class="mr-2">←</span> Annuler et retourner à la liste
                    </a>
                    
                    <button type="submit" 
                            class="bg-gradient-to-r from-primary to-primary-dark hover:from-primary-dark hover:to-primary text-white px-8 py-4 rounded-lg font-bold text-lg shadow-lg transition transform hover:scale-105">
                        💾 Enregistrer le paiement
                    </button>
                </div>
            </div>
            
        </form>
    </div>
    
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- JAVASCRIPT DYNAMIQUE -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <script>
    // 1. Filtrage des classes par cycle (Insensible à la casse)
    function filterClasses() {
        const selectedCycle = document.getElementById('cycle').value.trim().toLowerCase();
        const classSelect = document.getElementById('class_id');
        const options = classSelect.querySelectorAll('option');
        let hasVisibleOptions = false;

        options.forEach(option => {
            if (option.value === "") {
                option.style.display = 'block';
                option.textContent = selectedCycle ? "-- Sélectionner une classe --" : "-- D'abord, sélectionner un cycle --";
                return;
            }
            
            const optionCycle = (option.getAttribute('data-cycle') || '').trim().toLowerCase();
            
            if (!selectedCycle || optionCycle === selectedCycle) {
                option.style.display = 'block';
                hasVisibleOptions = true;
            } else {
                option.style.display = 'none';
                option.selected = false;
            }
        });

        if (!hasVisibleOptions) {
            classSelect.value = "";
        }
        
        // Réinitialiser les élèves si on change de cycle/classe
        resetAll();
    }

    // 2. Chargement AJAX du tableau des élèves
    function loadStudentsByClass() {
        const classId = document.getElementById('class_id').value;
        const yearId = document.getElementById('school_year_id').value;
        const tbody = document.getElementById('students-table-body');
        const container = document.getElementById('students-table-container');
        
        if (!classId || !yearId) {
            resetAll();
            return;
        }

        tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">⏳ Chargement des élèves en cours...</td></tr>';
        container.classList.remove('hidden');
        resetInstallments();

        const fetchUrl = "{{ route('app.students.by-class') }}?class_id=" + classId + "&year_id=" + yearId;

        fetch(fetchUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur serveur: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.message);
                
                tbody.innerHTML = '';
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Aucun élève trouvé dans cette classe pour cette année.</td></tr>';
                    return;
                }

                data.forEach(student => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-yellow-50 cursor-pointer transition';
                    tr.onclick = (e) => {
                        if(e.target.type !== 'checkbox') {
                            const cb = tr.querySelector('input[type="checkbox"]');
                            cb.checked = !cb.checked;
                            selectStudentForPayment(student, cb);
                        }
                    };

                    // Échapper les guillemets pour le JSON inline
                    const safeStudentJson = JSON.stringify(student).replace(/'/g, "&#39;").replace(/"/g, '&quot;');

                    tr.innerHTML = `
                        <td class="px-4 py-3">
                            <input type="checkbox" class="student-checkbox w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary" 
                                   onchange="selectStudentForPayment(null, this)" data-student='${safeStudentJson}'>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">${student.matricule || 'N/A'}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 font-semibold">${student.name}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error("Erreur AJAX:", error);
                tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-red-600 font-bold">❌ Erreur: ${error.message}<br><span class="text-xs font-normal text-gray-500">Vérifiez la console (F12) et votre contrôleur.</span></td></tr>`;
            });
    }

    // 3. Quand on coche un élève, afficher ses échéances
    function selectStudentForPayment(studentData, checkbox) {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => { if (cb !== checkbox) cb.checked = false; });

        if (!checkbox.checked) {
            resetInstallments();
            return;
        }

        const student = studentData || JSON.parse(checkbox.getAttribute('data-student').replace(/&quot;/g, '"'));
        
        document.getElementById('selected_student_id').value = student.id;
        document.getElementById('selected_enrollment_id').value = student.enrollment_id || '';

        const instContainer = document.getElementById('installments-container');
        instContainer.classList.remove('hidden');

        if (!student.enrollment_id) {
            instContainer.innerHTML = `
                <div class="bg-red-50 p-4 rounded-lg border-2 border-red-200 text-center">
                    <p class="text-sm font-bold text-red-800 mb-2">❌ ${student.name} n'a pas d'inscription pour l'année sélectionnée.</p>
                </div>`;
            return;
        }

        if (!student.installments || student.installments.length === 0) {
            instContainer.innerHTML = `
                <div class="bg-orange-50 p-4 rounded-lg border-2 border-orange-200 text-center">
                    <p class="text-sm font-bold text-orange-800 mb-2">⚠️ Aucune échéance générée pour ${student.name}.</p>
                </div>`;
            return;
        }

        let html = `
            <div class="bg-blue-50 p-6 rounded-lg border-2 border-blue-200 animate-fade-in">
                <h3 class="font-bold text-blue-900 mb-4 flex items-center text-lg">
                    <span class="mr-2">📅</span> Échéances à régler pour ${student.name}
                </h3>
                <div class="space-y-3">
        `;

        student.installments.forEach((inst, index) => {
            const safeDesc = (inst.description || '').replace(/'/g, "\\'");
            const formattedAmount = new Intl.NumberFormat('fr-FR').format(inst.amount);
            const formattedRemaining = new Intl.NumberFormat('fr-FR').format(inst.remaining);
            
            let statusHtml = `<p class="text-sm font-bold text-red-600 bg-red-50 px-2 py-1 rounded mt-1">Non payé : ${formattedRemaining} FCFA</p>`;
            if (inst.status === 'partial') {
                const formattedPaid = new Intl.NumberFormat('fr-FR').format(inst.paid_amount);
                statusHtml = `<p class="text-xs text-orange-600">Déjà payé : ${formattedPaid} FCFA</p>
                              <p class="text-sm font-bold text-red-600 bg-red-50 px-2 py-1 rounded mt-1">Reste : ${formattedRemaining} FCFA</p>`;
            } else if (inst.status === 'paid') {
                statusHtml = `<p class="text-sm font-bold text-green-600 bg-green-50 px-2 py-1 rounded mt-1">Payé</p>`;
            }

            html += `
                <label class="flex items-center justify-between p-4 bg-white border border-blue-200 rounded-lg cursor-pointer hover:bg-blue-100 hover:border-blue-400 transition shadow-sm group">
                    <div class="flex items-center">
                        <input type="radio" name="installment_radio" value="${inst.id}"
                               class="w-5 h-5 text-primary border-gray-300 focus:ring-primary"
                               ${index === 0 ? 'checked' : ''}
                               onchange="updateAmountAndInstallment(${inst.remaining}, ${inst.id}, '${safeDesc}', '${inst.type || 'tuition'}')">
                        <div class="ml-4">
                            <p class="text-sm font-bold text-gray-800 group-hover:text-primary transition">${inst.description}</p>
                            <p class="text-xs text-gray-500">Échéance le : <span class="font-semibold">${inst.due_date}</span></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-900">${formattedAmount} FCFA</p>
                        ${statusHtml}
                    </div>
                </label>
            `;
        });

        html += `</div></div>`;
        instContainer.innerHTML = html;

        // Déclencher le calcul pour la première échéance
        if (student.installments.length > 0) {
            const first = student.installments[0];
            const safeDesc = (first.description || '').replace(/'/g, "\\'");
            updateAmountAndInstallment(first.remaining, first.id, safeDesc, first.type || 'tuition');
        }
    }

    // 4. Réinitialisations (CORRIGÉ : plus d'erreur de syntaxe)
    function resetInstallments() {
        document.getElementById('installments-container').classList.add('hidden');
        document.getElementById('installments-container').innerHTML = '';
        document.getElementById('selected_student_id').value = '';
        document.getElementById('selected_enrollment_id').value = '';
        document.getElementById('selected_installment_id').value = '';
        document.getElementById('amount_to_pay').value = '';
        document.getElementById('payment_type').value = '';
        document.getElementById('payment_type_display').value = '-- Sélectionnez une échéance --';
    }

    function resetAll() {
        document.getElementById('students-table-container').classList.add('hidden');
        document.getElementById('students-table-body').innerHTML = '';
        resetInstallments();
    }

    // 5. Quand l'année change
    function handleYearChange() {
        const classSelect = document.getElementById('class_id');
        if (classSelect.value) {
            loadStudentsByClass();
        } else {
            resetAll();
        }
    }

    // 6. Mise à jour automatique du montant et du type
    function updateAmountAndInstallment(amount, installmentId, installmentDescription, installmentType) {
        const amountInput = document.getElementById('amount_to_pay');
        const installmentInput = document.getElementById('selected_installment_id');
        const paymentTypeInput = document.getElementById('payment_type');
        const paymentTypeDisplay = document.getElementById('payment_type_display');
        
        if (amountInput) amountInput.value = amount;
        if (installmentInput) installmentInput.value = installmentId;
        
        if (paymentTypeInput && paymentTypeDisplay) {
            if (installmentType === 'registration') {
                paymentTypeInput.value = 'registration';
                paymentTypeDisplay.value = '📝 Frais d\'inscription';
            } else {
                paymentTypeInput.value = 'tuition';
                paymentTypeDisplay.value = '📚 ' + installmentDescription;
            }
        }
        
        if (amountInput) {
            amountInput.classList.add('bg-green-100', 'border-green-400');
            setTimeout(() => amountInput.classList.remove('bg-green-100', 'border-green-400'), 800);
        }
    }

    // 7. Validation avant soumission
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const studentId = document.getElementById('selected_student_id').value;
        const enrollmentId = document.getElementById('selected_enrollment_id').value;
        const installmentId = document.getElementById('selected_installment_id').value;
        const amount = document.getElementById('amount_to_pay').value;
        const paymentType = document.getElementById('payment_type').value;
        const paymentMethod = document.querySelector('select[name="payment_method"]').value;
        const paymentDate = document.querySelector('input[name="payment_date"]').value;

        if (!studentId) { e.preventDefault(); alert('❌ Veuillez sélectionner un élève dans le tableau.'); return false; }
        if (!enrollmentId) { e.preventDefault(); alert('❌ Erreur : l\'inscription de l\'élève n\'a pas été trouvée.'); return false; }
        if (!installmentId) { e.preventDefault(); alert('❌ Veuillez cocher une échéance à payer.'); return false; }
        if (!amount || parseFloat(amount) <= 0) { e.preventDefault(); alert('❌ Le montant doit être supérieur à 0.'); return false; }
        if (!paymentType) { e.preventDefault(); alert('❌ Le type de paiement n\'a pas été défini.'); return false; }
        if (!paymentMethod) { e.preventDefault(); alert('❌ Veuillez sélectionner une méthode de paiement.'); return false; }
        if (!paymentDate) { e.preventDefault(); alert('❌ Veuillez sélectionner une date de paiement.'); return false; }

        return true;
    });

    // 8. Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        filterClasses(); // Filtre au chargement si un cycle est déjà sélectionné (old input)
        if (document.getElementById('class_id').value && document.getElementById('school_year_id').value) {
            loadStudentsByClass();
        }
    });
</script>
@endsection