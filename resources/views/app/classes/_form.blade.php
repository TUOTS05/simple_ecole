{{-- Formulaire commun création / modification d'une classe.
     Variables : $class (SchoolClass, éventuellement non enregistrée), $cycles, $levelsByCycle, $responsibles --}}
@php
    $icons = ['creche' => '🍼', 'maternelle' => '🧒', 'primaire' => '📚'];
    $inputClass = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent';
    $time = fn ($value) => $value ? substr($value, 0, 5) : null;
@endphp

<div class="space-y-6">

    <!-- Nom -->
    <div>
        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <span class="mr-2">🏷️</span> Nom de la classe ou du groupe *
        </label>
        <input type="text" name="name" value="{{ old('name', $class->name) }}" required
               placeholder="Ex: GS A, CP1 B, Les Poussins..."
               class="{{ $inputClass }} @error('name') border-red-500 @enderror">
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Cycle -->
    <div>
        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <span class="mr-2">🎓</span> Cycle *
        </label>
        <select name="cycle" id="cycle" required class="{{ $inputClass }} @error('cycle') border-red-500 @enderror">
            <option value="">-- Sélectionner un cycle --</option>
            @foreach($cycles as $value => $label)
                <option value="{{ $value }}" {{ old('cycle', $class->cycle) === $value ? 'selected' : '' }}>{{ $icons[$value] ?? '' }} {{ $label }}</option>
            @endforeach
        </select>
        @error('cycle')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Niveau -->
    <div>
        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <span class="mr-2">📊</span> Niveau *
        </label>
        <select name="level" id="level" required class="{{ $inputClass }} @error('level') border-red-500 @enderror">
            <option value="">-- Sélectionnez d'abord un cycle --</option>
        </select>
        @error('level')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Capacité -->
    <div>
        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <span class="mr-2">👥</span> Capacité (vide = {{ \App\Models\SchoolClass::DEFAULT_CAPACITY }})
        </label>
        <input type="number" name="capacity" value="{{ old('capacity', $class->capacity) }}" min="1" max="100"
               placeholder="{{ \App\Models\SchoolClass::DEFAULT_CAPACITY }}"
               class="{{ $inputClass }} @error('capacity') border-red-500 @enderror">
        @error('capacity')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-500 mt-1">Nombre maximum d'élèves dans cette classe</p>
    </div>

    <!-- Groupe de crèche : informations propres à la petite enfance -->
    <div id="creche-fields" class="hidden border border-pink-200 bg-pink-50/40 rounded-lg p-4 space-y-4">
        <h3 class="font-semibold text-gray-800">🍼 Groupe de crèche</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                <input type="text" name="code" value="{{ old('code', $class->code) }}" maxlength="20" placeholder="Ex: BB1"
                       class="{{ $inputClass }} @error('code') border-red-500 @enderror">
                @error('code') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Salle</label>
                <input type="text" name="room" value="{{ old('room', $class->room) }}" maxlength="100" placeholder="Ex: Salle des bébés"
                       class="{{ $inputClass }} @error('room') border-red-500 @enderror">
                @error('room') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Âge minimum (mois)</label>
                <input type="number" name="age_min_months" value="{{ old('age_min_months', $class->age_min_months) }}" min="0" max="72"
                       class="{{ $inputClass }} @error('age_min_months') border-red-500 @enderror">
                @error('age_min_months') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Âge maximum (mois)</label>
                <input type="number" name="age_max_months" value="{{ old('age_max_months', $class->age_max_months) }}" min="0" max="72"
                       class="{{ $inputClass }} @error('age_max_months') border-red-500 @enderror">
                @error('age_max_months') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ouverture</label>
                <input type="time" name="opening_time" value="{{ old('opening_time', $time($class->opening_time)) }}"
                       class="{{ $inputClass }} @error('opening_time') border-red-500 @enderror">
                @error('opening_time') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fermeture</label>
                <input type="time" name="closing_time" value="{{ old('closing_time', $time($class->closing_time)) }}"
                       class="{{ $inputClass }} @error('closing_time') border-red-500 @enderror">
                @error('closing_time') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-gray-500">Horaires laissés vides : ceux de la crèche s'appliquent (Crèche › Paramètres).</p>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Responsable du groupe</label>
            <select name="responsible_user_id" class="{{ $inputClass }} @error('responsible_user_id') border-red-500 @enderror">
                <option value="">-- Aucun --</option>
                @foreach($responsibles as $person)
                    <option value="{{ $person->id }}" {{ (string) old('responsible_user_id', $class->responsible_user_id) === (string) $person->id ? 'selected' : '' }}>
                        {{ $person->full_name }} ({{ $person->role_label }})
                    </option>
                @endforeach
            </select>
            @error('responsible_user_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

</div>

<script>
    (function () {
        const levelsByCycle = @json($levelsByCycle, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        const selectedLevel = @json(old('level', $class->level), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        const cycleSelect = document.getElementById('cycle');
        const levelSelect = document.getElementById('level');
        const crecheFields = document.getElementById('creche-fields');

        function updateForm() {
            const cycle = cycleSelect.value;
            levelSelect.innerHTML = '<option value="">-- Sélectionner un niveau --</option>';

            (levelsByCycle[cycle] || []).forEach(level => {
                const option = document.createElement('option');
                option.value = level;
                option.textContent = level;
                if (level === selectedLevel) option.selected = true;
                levelSelect.appendChild(option);
            });

            crecheFields.classList.toggle('hidden', cycle !== 'creche');
        }

        cycleSelect.addEventListener('change', updateForm);
        updateForm();
    })();
</script>
