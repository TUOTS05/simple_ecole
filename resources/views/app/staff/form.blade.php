@extends('layouts.app')

@section('title', $member->exists ? 'Modifier un membre du personnel' : 'Nouveau membre du personnel')
@section('page_title', 'Personnel')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ $member->exists ? $member->full_name : 'Nouveau membre du personnel' }}</h1>
            <p class="text-sm text-gray-500 mb-6">Un enseignant accède à ses classes ; un membre du personnel administratif, aux modules de sa fonction.</p>

            <form action="{{ $member->exists ? route('app.staff.update', $member) : route('app.staff.store') }}" method="POST"
                  x-data="{ profile: '{{ old('profile', $profile) }}', newFunction: {{ old('new_function') || $functions->isEmpty() ? 'true' : 'false' }} }">
                @csrf
                @if($member->exists) @method('PUT') @endif
                @if($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <ul class="list-disc list-inside text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Profil : seulement à la création --}}
                    @unless($member->exists)
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profil *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="profile === 'staff' ? 'border-primary bg-primary/10' : 'border-gray-200'">
                                <input type="radio" name="profile" value="staff" x-model="profile" class="mt-1">
                                <span><span class="font-semibold block">Personnel administratif</span><span class="text-xs text-gray-500">Secrétaire, comptable, surveillant… accès selon sa fonction</span></span>
                            </label>
                            <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="profile === 'teacher' ? 'border-primary bg-primary/10' : 'border-gray-200'">
                                <input type="radio" name="profile" value="teacher" x-model="profile" class="mt-1">
                                <span><span class="font-semibold block">Enseignant</span><span class="text-xs text-gray-500">Appel et notes de ses classes, depuis l'espace enseignant</span></span>
                            </label>
                        </div>
                    </div>
                    @endunless

                    {{-- Fonction et droits d'accès (personnel administratif) --}}
                    <div class="md:col-span-2" x-show="profile === 'staff'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fonction *</label>
                        <div x-show="! newFunction" class="flex flex-col sm:flex-row gap-2">
                            <select name="staff_function_id" :disabled="newFunction || profile !== 'staff'" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                                <option value="">— Choisir —</option>
                                @foreach($functions as $function)
                                    <option value="{{ $function->id }}" @selected(old('staff_function_id', $member->staff_function_id) == $function->id)>{{ $function->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="newFunction = true" class="px-4 py-2 border border-primary text-primary-dark rounded-lg font-semibold whitespace-nowrap">+ Nouvelle fonction</button>
                        </div>

                        <div x-show="newFunction" class="border border-primary/40 rounded-lg p-4 space-y-4 bg-primary/5">
                            <input type="hidden" name="new_function" :value="newFunction && profile === 'staff' ? 1 : 0">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la nouvelle fonction *</label>
                                <input type="text" name="new_function_name" value="{{ old('new_function_name') }}" :disabled="! newFunction" placeholder="Ex. Caissier, Secrétaire, Surveillant"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2">Droits d'accès à la plateforme</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach($modules as $key => $module)
                                        <label class="flex items-start gap-2 text-sm">
                                            <input type="checkbox" name="permissions[]" value="{{ $key }}" :disabled="! newFunction" class="mt-1" @checked(in_array($key, old('permissions', [])))>
                                            <span><span class="font-semibold">{{ $module['icon'] }} {{ $module['label'] }}</span><span class="block text-xs text-gray-500">{{ $module['description'] }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @if($functions->isNotEmpty())
                                <button type="button" @click="newFunction = false" class="text-sm text-gray-600 underline">Choisir plutôt une fonction existante</button>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Les fonctions et leurs accès se gèrent aussi depuis <a href="{{ route('app.staff-functions.index') }}" class="underline">Fonctions & accès</a>.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $member->first_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $member->last_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $member->email) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $member->phone) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe {{ $member->exists ? '(laisser vide pour ne pas changer)' : '*' }}</label>
                        <input type="password" name="password" @required(! $member->exists) class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation" @required(! $member->exists) class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Genre *</label>
                        <div class="flex space-x-4">
                            @foreach(['M' => 'Homme', 'F' => 'Femme'] as $value => $label)
                            <label class="flex items-center space-x-2">
                                <input type="radio" name="gender" value="{{ $value }}" {{ old('gender', $member->gender) === $value ? 'checked' : '' }} required class="form-radio text-primary">
                                <span>{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">Enregistrer</button>
                    <a href="{{ route('app.staff.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
