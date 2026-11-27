{{-- Formulaire d'une personne autorisée (ajout ou modification).
     Variables : $pickup (AuthorizedPickup), $action, $method ('POST' | 'PUT'), $relationships, $formId --}}
@php
    $input = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm';
    // Les anciennes saisies ne réapparaissent que dans le formulaire qui a été envoyé.
    $isSubmitted = old('_form') === $formId;
    $value = fn (string $field, $default = null) => $isSubmitted ? old($field) : ($default ?? $pickup->{$field});
@endphp
<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-3">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif
    <input type="hidden" name="_form" value="{{ $formId }}">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Prénom *</label>
            <input type="text" name="first_name" value="{{ $value('first_name') }}" required maxlength="100" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Nom *</label>
            <input type="text" name="last_name" value="{{ $value('last_name') }}" required maxlength="100" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Lien avec l'enfant *</label>
            <input type="text" name="relationship" value="{{ $value('relationship') }}" required maxlength="50" list="pickup-relationships" placeholder="Ex : Tante" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Téléphone</label>
            <input type="tel" name="phone" value="{{ $value('phone') }}" maxlength="20" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Statut *</label>
            <select name="status" class="{{ $input }} bg-white">
                @foreach(\App\Models\AuthorizedPickup::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ $value('status', $pickup->status ?? 'authorized') === $key ? 'selected' : '' }}>{{ $key === 'forbidden' ? '⛔' : '✅' }} {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Photo (facultative)</label>
            <input type="file" name="photo" accept="image/jpeg,image/png" class="w-full text-sm">
            @if($pickup->photo)
            <label class="flex items-center gap-1 text-xs text-gray-600 mt-1">
                <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300"> Retirer la photo actuelle
            </label>
            @endif
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Valable à partir du</label>
            <input type="date" name="starts_on" value="{{ $value('starts_on', $pickup->starts_on?->format('Y-m-d')) }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Jusqu'au</label>
            <input type="date" name="ends_on" value="{{ $value('ends_on', $pickup->ends_on?->format('Y-m-d')) }}" class="{{ $input }}">
        </div>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Commentaire</label>
        <textarea name="comment" rows="2" maxlength="1000" class="{{ $input }}">{{ $value('comment') }}</textarea>
    </div>
    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
        {{ $method === 'PUT' ? 'Enregistrer' : 'Ajouter' }}
    </button>
</form>
