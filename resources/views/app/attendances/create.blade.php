@extends('layouts.app')

@section('title', 'Faire l\'appel')
@section('page_title', 'Faire l\'appel')

@section('content')
    <div class="max-w-6xl mx-auto">
        
        <!-- Sélecteurs -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-primary">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">⚙️</span> 1. Sélectionner la classe et la date
            </h2>
            <form method="GET" action="{{ route('app.attendances.create') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Classe *</label>
                    <select name="class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        <option value="">-- Choisir --</option>
                        @foreach($classes as $tc)
                            <option value="{{ $tc->id }}" data-cycle="{{ $tc->cycle ?? 'Cycle' }}" {{ ($selectedClassId ?? '') == $tc->id ? 'selected' : '' }}>
                                {{ $tc->name }} ({{ $tc->cycle ?? 'Cycle' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Période *</label>
                    <select name="period" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        <option value="matin" {{ ($selectedPeriod ?? 'matin') === 'matin' ? 'selected' : '' }}>Matin 8h00 - 12h00</option>
                        <option value="apres_midi" {{ ($selectedPeriod ?? '') === 'apres_midi' ? 'selected' : '' }}>Après-midi 14h00 - 16h00/17h00</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                    <input type="date" name="date" value="{{ $selectedDate ?? date('Y-m-d') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                        🔍 Charger les élèves
                    </button>
                </div>
            </form>
            <div id="attendance-schedule-info" class="mt-4 p-4 rounded-lg bg-blue-50 border border-blue-200 text-sm text-blue-700">
                <strong>Horaires de pointage :</strong> Sélectionnez une classe pour afficher la plage horaire applicable.
            </div>
        </div>

        <!-- Formulaire d'appel -->
        @if(isset($students) && $students->count() > 0)
            <form action="{{ route('app.attendances.store') }}" method="POST">
                @csrf
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <input type="hidden" name="period" value="{{ $selectedPeriod ?? 'matin' }}">

                <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-accent">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <span class="mr-2">✅</span> 2. Pointer les élèves ({{ $students->count() }})
                        </h2>
                        <p class="text-sm text-gray-500">Date : <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong></p>
                    </div>

                    <!-- Boutons d'action rapide -->
                    <div class="flex space-x-2 mb-4">
                        <button type="button" onclick="setAllStatus('present')" class="bg-accent hover:bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold">
                            Tous Présents
                        </button>
                        <button type="button" onclick="setAllStatus('absent')" class="bg-danger hover:bg-red-600 text-white px-4 py-2 rounded text-sm font-semibold">
                            Tous Absents
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 w-1/3">Élève</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-accent">Présent</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-danger">Absent</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-secondary">Retard</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-primary">Excusé</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Note (optionnel)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        $existing = $existingAttendances->get($student->id);
                                        $currentStatus = $existing ? $existing->status : 'present';
                                    @endphp
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="font-semibold">{{ $student->last_name }} {{ $student->first_name }}</div>
                                            <div class="text-xs text-gray-500 font-mono">{{ $student->matricule ?? 'N/A' }}</div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="present" 
                                                   {{ $currentStatus === 'present' ? 'checked' : '' }} 
                                                   class="w-5 h-5 text-green-600 focus:ring-green-500 status-radio">
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="absent" 
                                                   {{ $currentStatus === 'absent' ? 'checked' : '' }} 
                                                   class="w-5 h-5 text-red-600 focus:ring-red-500 status-radio">
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="late" 
                                                   {{ $currentStatus === 'late' ? 'checked' : '' }} 
                                                   class="w-5 h-5 text-yellow-500 focus:ring-yellow-500 status-radio">
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <input type="radio" name="attendances[{{ $student->id }}][status]" value="excused" 
                                                   {{ $currentStatus === 'excused' ? 'checked' : '' }} 
                                                   class="w-5 h-5 text-blue-600 focus:ring-blue-500 status-radio">
                                        </td>
                                        <td class="py-3 px-4">
                                            <input type="text" name="attendances[{{ $student->id }}][notes]" 
                                                   value="{{ $existing->notes ?? '' }}" 
                                                   placeholder="Ex: Malade..."
                                                   class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-primary">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-gradient-to-r from-primary to-primary-dark text-white px-8 py-4 rounded-lg font-bold text-lg shadow-lg transition transform hover:scale-105">
                        💾 Enregistrer l'appel
                    </button>
                </div>
            </form>
        @elseif(isset($selectedClassId))
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg text-center">
                <p class="text-yellow-800 text-lg">⚠️ Aucun élève actif trouvé dans cette classe.</p>
            </div>
        @endif

    </div>

    <script>
        function setAllStatus(status) {
            document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(radio => {
                radio.checked = true;
            });
        }

        function updateScheduleInfo() {
            const classSelect = document.querySelector('select[name="class_id"]');
            const periodSelect = document.querySelector('select[name="period"]');
            const infoBox = document.getElementById('attendance-schedule-info');
            if (!classSelect || !periodSelect || !infoBox) return;

            const selectedOption = classSelect.options[classSelect.selectedIndex];
            const cycle = selectedOption ? selectedOption.getAttribute('data-cycle') : null;
            const period = periodSelect.value;
            const periodLabel = period === 'apres_midi' ? 'Après-midi 14h00 - 16h00/17h00' : 'Matin 8h00 - 12h00';
            let message = `<strong>Pointage pour :</strong> ${periodLabel}. Un seul pointage est enregistré pour la journée.`;

            if (cycle === 'maternelle' || cycle === 'primaire') {
                message += ' Ce cycle suit les horaires maternelle/primaire.';
            }

            infoBox.innerHTML = message;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.querySelector('select[name="class_id"]');
            const periodSelect = document.querySelector('select[name="period"]');
            if (classSelect) {
                classSelect.addEventListener('change', updateScheduleInfo);
            }
            if (periodSelect) {
                periodSelect.addEventListener('change', updateScheduleInfo);
            }
            updateScheduleInfo();
        });
    </script>
@endsection