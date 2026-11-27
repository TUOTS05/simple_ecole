{{-- Structures activées pour une école (crèche, maternelle, primaire) : une ou plusieurs.
     Usage : <x-structures-field :selected="$school->structureList()" /> --}}
@props(['selected' => []])

@php
    $checked = (array) old('structures', $selected);
    $hints = ['creche' => '0-3 ans', 'maternelle' => 'TPS à GS', 'primaire' => 'CP1 à CM2'];
@endphp

<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Structures de l'établissement *</label>
    <div class="flex flex-wrap gap-3">
        @foreach(\App\Models\School::STRUCTURES as $value => $label)
            <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 {{ in_array($value, $checked, true) ? 'border-primary bg-primary/5' : 'border-gray-300' }}">
                <input type="checkbox" name="structures[]" value="{{ $value }}" {{ in_array($value, $checked, true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-primary focus:ring-primary">
                <span class="text-sm text-gray-800">{{ $label }} <span class="text-gray-500">({{ $hints[$value] }})</span></span>
            </label>
        @endforeach
    </div>
    <p class="text-xs text-gray-500 mt-1">Cochez tout ce que l'établissement accueille : un même enfant peut passer de la crèche à la maternelle puis au primaire.</p>
    @error('structures') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('structures.*') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
</div>
