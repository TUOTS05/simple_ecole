@extends('layouts.app')

@section('title', 'Configurer les matières')
@section('page_title', 'Configurer les matières')

@section('content')

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">

        <form action="{{ route('app.subjects.store') }}" method="POST">
            @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <ul class="list-disc list-inside text-red-700">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @csrf

            <div class="space-y-6">

                <!-- Cycle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cycle *</label>
                    <select name="cycle" id="cycle" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        <option value="">-- Sélectionner un cycle --</option>
                        @if($school->isMaternelle() || $school->isBoth())
                        <option value="maternelle" @selected(old('cycle') === 'maternelle')>🧒 Maternelle</option>
                        @endif
                        @if($school->isPrimaire() || $school->isBoth())
                        <option value="primaire" @selected(old('cycle') === 'primaire')> Primaire</option>
                        @endif
                    </select>
                </div>

                <!-- Niveau -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Niveau *</label>
                    <select name="level" id="level" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        <option value="">-- Sélectionnez d'abord un cycle --</option>
                    </select>
                </div>

                <!-- Matières -->
                <div id="subjects-container" class="hidden">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Matières à configurer</h3>
                    <div class="grid grid-cols-12 gap-4 mb-2 font-semibold text-sm text-gray-700">
                        <div class="col-span-4">Matière</div>
                        <div class="col-span-3">Domaine <span class="font-normal text-gray-500">(facultatif)</span></div>
                        <div class="col-span-2">Coefficient</div>
                        <div class="col-span-2">Notée sur</div>
                        <div class="col-span-1"></div>
                    </div>
                    <datalist id="subject-domains">
                        @foreach($domains as $domain)<option value="{{ $domain }}">@endforeach
                    </datalist>
                    <p class="text-xs text-gray-500 mb-3">La liste proposée se modifie dans Paramètres › Personnalisation › Matières. Les matières s'affichent sur le bulletin dans cet ordre.</p>
                    <div id="subjects-list" class="space-y-3">
                        <!-- Les matières seront ajoutées dynamiquement -->
                    </div>
                    <button type="button" onclick="addSubject()" class="mt-4 bg-secondary hover:bg-yellow-400 text-gray-800 px-4 py-2 rounded-lg font-semibold transition">
                        + Ajouter une matière
                    </button>
                </div>

            </div>

            <div class="mt-8 flex space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                    Enregistrer les matières
                </button>
                <a href="{{ route('app.subjects.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                    Annuler
                </a>
            </div>

        </form>

    </div>
</div>

<script>
    const levelsByCycle = @json($levelsByCycle);
    const defaultSubjects = @json($defaultSubjects);
    // Saisie précédente (en cas d'erreur de validation) pour ne rien perdre
    const oldLevel = @json(old('level'));
    const oldSubjects = @json(array_values(old('subjects', [])));

    const cycleSelect = document.getElementById('cycle');
    const levelSelect = document.getElementById('level');
    const subjectsContainer = document.getElementById('subjects-container');
    const subjectsList = document.getElementById('subjects-list');

    function fillLevels(cycle) {
        levelSelect.innerHTML = '<option value="">-- Sélectionner un niveau --</option>';
        if (cycle && levelsByCycle[cycle]) {
            levelsByCycle[cycle].forEach(level => {
                const option = document.createElement('option');
                option.value = level;
                option.textContent = level;
                levelSelect.appendChild(option);
            });
        }
    }

    cycleSelect.addEventListener('change', function() {
        fillLevels(this.value);
        subjectsContainer.classList.add('hidden');
        subjectsList.innerHTML = '';
    });

    levelSelect.addEventListener('change', function() {
        const cycle = cycleSelect.value;
        const level = this.value;

        if (cycle && level) {
            subjectsContainer.classList.remove('hidden');
            subjectsList.innerHTML = '';

            // Ajouter les matières par défaut avec max_score = 20
            const subjects = defaultSubjects[cycle] || [];
            subjects.forEach((subject, index) => {
                addSubject(subject, 1, 20, ''); // 20 par défaut
            });
        }
    });

    function escapeAttr(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function addSubject(name = '', coefficient = 1, maxScore = 20, domain = '') {
        const div = document.createElement('div');
        div.className = 'grid grid-cols-12 gap-4 items-center';
        const index = subjectsList.children.length;
        div.innerHTML = `
        <div class="col-span-4">
            <input type="text" name="subjects[${index}][name]" value="${escapeAttr(name)}" placeholder="Nom de la matière" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="col-span-3">
            <input type="text" name="subjects[${index}][domain]" value="${escapeAttr(domain)}" placeholder="Ex. Français" list="subject-domains" maxlength="100"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="col-span-2">
            <input type="number" name="subjects[${index}][coefficient]" value="${escapeAttr(coefficient)}" min="1" max="10" step="0.5" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="col-span-2">
            <select name="subjects[${index}][max_score]" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="10" ${maxScore == 10 ? 'selected' : ''}>Sur 10</option>
                <option value="20" ${maxScore == 20 ? 'selected' : ''}>Sur 20</option>
                <option value="40" ${maxScore == 40 ? 'selected' : ''}>Sur 40</option>
                <option value="50" ${maxScore == 50 ? 'selected' : ''}>Sur 50</option>
                <option value="100" ${maxScore == 100 ? 'selected' : ''}>Sur 100</option>
            </select>
        </div>
        <div class="col-span-1">
            <button type="button" onclick="removeSubject(this)" class="text-red-600 hover:text-red-800">
                🗑️
            </button>
        </div>
    `;
        subjectsList.appendChild(div);
    }

    function removeSubject(button) {
        button.closest('#subjects-list > div').remove();
        // Réindexer TOUS les champs de chaque ligne (nom, coefficient ET barème),
        // sinon les champs d'une même matière se retrouvent sous des index différents.
        Array.from(subjectsList.children).forEach((child, index) => {
            child.querySelectorAll('[name^="subjects["]').forEach(field => {
                field.name = field.name.replace(/^subjects\[\d+\]/, `subjects[${index}]`);
            });
        });
    }

    // Restauration après une erreur de validation
    if (cycleSelect.value) {
        fillLevels(cycleSelect.value);
        if (oldLevel) {
            levelSelect.value = oldLevel;
            if (levelSelect.value === oldLevel) {
                subjectsContainer.classList.remove('hidden');
                if (oldSubjects.length) {
                    oldSubjects.forEach(s => addSubject(s.name ?? '', s.coefficient ?? 1, s.max_score ?? 20, s.domain ?? ''));
                } else {
                    (defaultSubjects[cycleSelect.value] || []).forEach(name => addSubject(name, 1, 20));
                }
            }
        }
    }
</script>

@endsection