@extends('layouts.app')

@section('title', $member->exists ? 'Modifier un membre du personnel' : 'Nouveau membre du personnel')
@section('page_title', 'Personnel')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ $member->exists ? $member->full_name : 'Nouveau membre du personnel' }}</h1>
            <p class="text-sm text-gray-500 mb-6">Les accès de ce compte découlent de la fonction choisie.</p>

            @if($functions->isEmpty())
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-yellow-800">
                    Aucune fonction n'est encore définie. <a href="{{ route('app.staff-functions.index') }}" class="underline font-semibold">Créez d'abord vos fonctions</a>.
                </div>
            @endif

            <form action="{{ $member->exists ? route('app.staff.update', $member) : route('app.staff.store') }}" method="POST">
                @csrf
                @if($member->exists) @method('PUT') @endif
                @if($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <ul class="list-disc list-inside text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fonction *</label>
                        <select name="staff_function_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            <option value="">— Choisir —</option>
                            @foreach($functions as $function)
                                <option value="{{ $function->id }}" @selected(old('staff_function_id', $member->staff_function_id) == $function->id)>{{ $function->name }}</option>
                            @endforeach
                        </select>
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
