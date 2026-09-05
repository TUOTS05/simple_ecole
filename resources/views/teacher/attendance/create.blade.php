@extends('layouts.app')

@section('title', 'Faire l\'appel')
@section('page_title', 'Faire l\'appel : ' . ($class->name ?? 'Sélection'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-primary">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="mr-2">⚙️</span> 1. Paramètres de l'appel
        </h2>
        <form method="GET" action="{{ route('teacher.attendance.create') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classe *</label>
                <select name="class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($teacherClasses as $tc)
                        <option value="{{ $tc->id }}" {{ ($selectedClassId ?? '') == $tc->id ? 'selected' : '' }}>
                            {{ $tc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Période *</label>
                <!-- ID AJOUTÉ POUR LE SCRIPT -->
                <select id="periodSelect" name="period" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                    <option value="matin" {{ ($selectedPeriod ?? 'matin') === 'matin' ? 'selected' : '' }}>Matin</option>
                    <option value="apres_midi" {{ ($selectedPeriod ?? '') === 'apres_midi' ? 'selected' : '' }}>Après-midi</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                <input type="date" name="date" value="{{ $selectedDate ?? date('Y-m-d') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white">
            </div>
            <div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    🔍 Charger
                </button>
            </div>
        </form>
    </div>

    @if(isset($students) && $students->count() > 0)

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-secondary">
            <h2 class="text-xl font-bold text-gray-800 mb-2 flex items-center">
                <span class="mr-2">📷</span> 2. Scanner les cartes scolaires (optionnel)
            </h2>
            <p class="text-sm text-gray-500 mb-4">Scannez le QR code de la carte de chaque élève avec la caméra : il sera automatiquement pointé « Présent » dans la liste ci-dessous.</p>

            <div class="flex items-center gap-3 mb-4">
                <button type="button" id="startScanBtn" class="bg-secondary hover:bg-yellow-500 text-gray-900 px-5 py-2 rounded-lg font-semibold text-sm transition">
                    Démarrer le scanner
                </button>
                <button type="button" id="stopScanBtn" class="hidden bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg font-semibold text-sm transition">
                    Arrêter le scanner
                </button>
            </div>

            <div id="qr-reader" class="hidden mx-auto" style="width: 300px; max-width: 100%;"></div>
            <div id="scanFeedback" class="mt-3 text-center font-semibold text-sm"></div>
        </div>

        <form action="{{ route('teacher.attendance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <!-- ID AJOUTÉ ET VALEUR FORCÉE DEPUIS LE CONTRÔLEUR -->
            <input type="hidden" id="periodHidden" name="period" value="{{ $selectedPeriod ?? 'matin' }}">

            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-accent mt-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <span class="mr-2">✅</span> 3. Pointer les élèves ({{ $students->count() }})
                    </h2>
                    <div class="flex space-x-2">
                        <button type="button" onclick="setAllStatus('present')" class="bg-green-100 hover:bg-green-200 text-green-700 px-4 py-2 rounded-lg text-sm font-semibold transition">✓ Tous Présents</button>
                        <button type="button" onclick="setAllStatus('absent')" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-semibold transition">✗ Tous Absents</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 w-1/3">Élève</th>
                                <th class="text-center py-3 px-4 text-sm font-semibold text-green-600">Présent</th>
                                <th class="text-center py-3 px-4 text-sm font-semibold text-red-600">Absent</th>
                                <th class="text-center py-3 px-4 text-sm font-semibold text-yellow-600">Retard</th>
                                <th class="text-center py-3 px-4 text-sm font-semibold text-blue-600">Excusé</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Note</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            @foreach($students as $student)
                                @php
                                    $existing = $existingAttendances->get($student->id);
                                    $currentStatus = $existing ? $existing->status : 'present';
                                @endphp
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition"
                                    data-student-id="{{ $student->id }}">
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-gray-800">{{ $student->last_name }} {{ $student->first_name }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ $student->matricule ?? 'N/A' }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-center"><input type="radio" name="attendances[{{ $student->id }}][status]" value="present" {{ $currentStatus === 'present' ? 'checked' : '' }} class="w-5 h-5 text-green-600 focus:ring-green-500 cursor-pointer"></td>
                                    <td class="py-3 px-4 text-center"><input type="radio" name="attendances[{{ $student->id }}][status]" value="absent" {{ $currentStatus === 'absent' ? 'checked' : '' }} class="w-5 h-5 text-red-600 focus:ring-red-500 cursor-pointer"></td>
                                    <td class="py-3 px-4 text-center"><input type="radio" name="attendances[{{ $student->id }}][status]" value="late" {{ $currentStatus === 'late' ? 'checked' : '' }} class="w-5 h-5 text-yellow-500 focus:ring-yellow-500 cursor-pointer"></td>
                                    <td class="py-3 px-4 text-center"><input type="radio" name="attendances[{{ $student->id }}][status]" value="excused" {{ $currentStatus === 'excused' ? 'checked' : '' }} class="w-5 h-5 text-blue-600 focus:ring-blue-500 cursor-pointer"></td>
                                    <td class="py-3 px-4"><input type="text" name="attendances[{{ $student->id }}][notes]" value="{{ $existing->notes ?? '' }}" placeholder="Ex: Malade..." class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-primary focus:border-primary"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-6">
                <a href="{{ route('teacher.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">Annuler</a>
                <button type="submit" class="bg-gradient-to-r from-primary to-primary-dark text-white px-8 py-3 rounded-lg font-bold shadow-lg transition transform hover:scale-105">💾 Enregistrer l'appel</button>
            </div>
        </form>
    @elseif(isset($selectedClassId))
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-xl text-center">
            <p class="text-yellow-800 text-lg">⚠️ Aucun élève actif trouvé dans cette classe.</p>
        </div>
    @endif
</div>

@if(isset($students) && $students->count() > 0)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    @endpush
@endif

<script>
    // 1. Synchronisation infaillible au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('periodSelect');
        const hidden = document.getElementById('periodHidden');
        if(select && hidden) {
            hidden.value = select.value;
        }
    });

    // 2. Synchronisation infaillible à chaque changement du menu déroulant
    document.getElementById('periodSelect').addEventListener('change', function() {
        document.getElementById('periodHidden').value = this.value;
    });

    // 3. Fonction pour tout cocher
    function setAllStatus(status) {
        document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(radio => {
            radio.checked = true;
        });
    }

    // 4. Scanner QR des cartes scolaires : la carte encode un code chiffré (pas de donnée
    // lisible), donc son identification se fait côté serveur (route scan-lookup) qui vérifie
    // aussi que l'élève appartient bien à la classe chargée.
    (function() {
        const startBtn = document.getElementById('startScanBtn');
        const stopBtn = document.getElementById('stopScanBtn');
        const readerEl = document.getElementById('qr-reader');
        const feedback = document.getElementById('scanFeedback');
        if (!startBtn) return;

        const currentClassId = {{ $selectedClassId ?? 'null' }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        let html5QrCode = null;
        const lastScanAt = {};

        function showFeedback(message, isError) {
            feedback.textContent = message;
            feedback.className = 'mt-3 text-center font-semibold text-sm ' + (isError ? 'text-red-600' : 'text-green-600');
        }

        function handleDecodedCode(rawCode) {
            const code = (rawCode || '').trim();
            if (!code) return;

            const now = Date.now();
            if (lastScanAt[code] && (now - lastScanAt[code]) < 2000) {
                return; // évite les doublons pendant que la carte reste face à la caméra
            }
            lastScanAt[code] = now;

            fetch('{{ route('teacher.attendance.scan-lookup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ code: code, class_id: currentClassId }),
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showFeedback('❌ ' + (data.message || 'Code non reconnu.'), true);
                        return;
                    }

                    const row = document.querySelector('#studentsTableBody tr[data-student-id="' + data.student_id + '"]');
                    if (!row) {
                        showFeedback('❌ Élève introuvable dans la liste affichée.', true);
                        return;
                    }

                    const presentRadio = row.querySelector('input[type="radio"][value="present"]');
                    if (presentRadio) {
                        presentRadio.checked = true;
                    }
                    row.classList.add('bg-green-50');
                    showFeedback('✅ ' + data.name + ' pointé(e) présent(e).', false);
                })
                .catch(() => showFeedback('❌ Erreur réseau pendant la vérification du code.', true));
        }

        startBtn.addEventListener('click', function() {
            if (typeof Html5Qrcode === 'undefined') {
                showFeedback('Le scanner QR n\'a pas pu se charger. Vérifiez votre connexion internet.', true);
                return;
            }

            readerEl.classList.remove('hidden');
            html5QrCode = new Html5Qrcode('qr-reader');
            html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 220 },
                handleDecodedCode,
                () => {}
            ).then(function() {
                startBtn.classList.add('hidden');
                stopBtn.classList.remove('hidden');
            }).catch(function(err) {
                readerEl.classList.add('hidden');
                showFeedback('Impossible d\'accéder à la caméra : ' + err, true);
            });
        });

        stopBtn.addEventListener('click', function() {
            if (!html5QrCode) return;
            html5QrCode.stop().then(function() {
                html5QrCode.clear();
                readerEl.classList.add('hidden');
                startBtn.classList.remove('hidden');
                stopBtn.classList.add('hidden');
            });
        });
    })();
</script>
@endsection