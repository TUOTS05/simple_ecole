{{-- Formulaire commun création / modification d'un membre du personnel.
     Variables : $staffUser, $selectedRole, $roles, $groups, $roleDefaults, $permissionsMode, $checkedPermissions --}}
@if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <ul class="list-disc list-inside text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div x-data="{
        role: @js($selectedRole),
        mode: @js($permissionsMode),
        defaults: @js($roleDefaults),
        custom: @js(array_values($checkedPermissions)),
        init() {
            if (this.mode === 'default') this.resetToDefaults();
            this.$watch('role', () => { if (this.mode === 'default') this.resetToDefaults(); });
            this.$watch('mode', (value) => { if (value === 'default') this.resetToDefaults(); });
        },
        resetToDefaults() { this.custom = [...(this.defaults[this.role] || [])]; },
    }">

    {{-- Identité --}}
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Identité</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
            <input type="text" name="first_name" value="{{ old('first_name', $staffUser->first_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
            <input type="text" name="last_name" value="{{ old('last_name', $staffUser->last_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email (identifiant de connexion) *</label>
            <input type="email" name="email" value="{{ old('email', $staffUser->email) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
            <input type="tel" name="phone" value="{{ old('phone', $staffUser->phone) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Mot de passe {{ $staffUser->exists ? '(laisser vide pour ne pas le changer)' : '*' }}
            </label>
            <input type="password" name="password" {{ $staffUser->exists ? '' : 'required' }} autocomplete="new-password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe {{ $staffUser->exists ? '' : '*' }}</label>
            <input type="password" name="password_confirmation" {{ $staffUser->exists ? '' : 'required' }} autocomplete="new-password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Genre *</label>
            <div class="flex space-x-4">
                @foreach(['M' => 'Homme', 'F' => 'Femme'] as $value => $label)
                    <label class="flex items-center space-x-2">
                        <input type="radio" name="gender" value="{{ $value }}" @checked(old('gender', $staffUser->gender) === $value) required class="form-radio text-primary">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Rôle --}}
    <h3 class="text-lg font-semibold text-gray-800 mt-8 mb-4">Rôle</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($roles as $value => $label)
            <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer transition"
                   :class="role === @js($value) ? 'border-primary bg-primary/5' : 'border-gray-200 hover:bg-gray-50'">
                <input type="radio" name="role" value="{{ $value }}" x-model="role" class="form-radio text-primary">
                <span class="font-medium text-gray-800">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    <p x-show="role === 'teacher'" class="text-sm text-gray-500 mt-3">
        L'enseignant travaille dans son espace Enseignant (appel, notes de ses classes). Les droits ci-dessous lui ouvrent en plus des écrans d'administration.
    </p>

    {{-- Droits --}}
    <h3 class="text-lg font-semibold text-gray-800 mt-8 mb-2">Droits d'accès</h3>
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="permissions_mode" value="default" x-model="mode" class="form-radio text-primary">
            <span>Droits par défaut du rôle</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="permissions_mode" value="custom" x-model="mode" class="form-radio text-primary">
            <span>Personnaliser pour cette personne</span>
        </label>
    </div>
    <p x-show="mode === 'default'" class="text-sm text-gray-500 mb-4">
        Les droits suivent le rôle choisi. Choisissez « Personnaliser » pour cocher ou décocher des droits.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" :class="mode === 'default' ? 'opacity-60' : ''">
        @foreach($groups as $groupLabel => $permissions)
            <fieldset class="border border-gray-200 rounded-lg p-4">
                <legend class="px-2 text-sm font-semibold text-gray-700">{{ $groupLabel }}</legend>
                <div class="space-y-2">
                    @foreach($permissions as $key => $label)
                        <label class="flex items-start gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" x-model="custom"
                                   :disabled="mode === 'default'" class="form-checkbox text-primary mt-0.5 rounded">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
    <p class="text-xs text-gray-500 mt-3">
        La gestion du personnel et des droits reste réservée au directeur.
    </p>
</div>
