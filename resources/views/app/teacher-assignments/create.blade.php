@extends('layouts.app')

@section('title', 'Assigner un enseignant')
@section('page_title', 'Nouvelle assignation')

@section('content')
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            
            <form action="{{ route('app.teacher-assignments.store') }}" method="POST">
                @csrf
                
                @if($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <ul class="list-disc list-inside text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-6">
                    
                    <!-- Sélection de la classe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Classe *</label>
                        <select name="school_class_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary @error('school_class_id') border-red-500 @enderror">
                            <option value="">-- Choisir une classe --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('school_class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }} ({{ $class->cycle }} - {{ $class->level }})
                                </option>
                            @endforeach
                        </select>
                        @error('school_class_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Sélection de l'enseignant -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Enseignant *</label>
                        <select name="user_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary @error('user_id') border-red-500 @enderror">
                            <option value="">-- Choisir un enseignant --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('user_id') == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->last_name }} {{ $teacher->first_name }} ({{ $teacher->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        @if($teachers->count() === 0)
                            <p class="text-yellow-600 text-sm mt-1">⚠️ Aucun utilisateur avec le rôle "teacher" n'existe. Créez d'abord un enseignant dans les Utilisateurs.</p>
                        @endif
                    </div>

                    <!-- Type d'assignation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fonction dans la classe *</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="is_main_teacher" value="1" checked class="form-radio text-primary">
                                <span class="text-gray-700">Enseignant Titulaire (Principal)</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="is_main_teacher" value="0" class="form-radio text-gray-500">
                                <span class="text-gray-700">Enseignant Adjoint</span>
                            </label>
                        </div>
                    </div>

                </div>
                
                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Enregistrer l'assignation
                    </button>
                    <a href="{{ route('app.teacher-assignments.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                </div>
                
            </form>
            
        </div>
    </div>
    
@endsection