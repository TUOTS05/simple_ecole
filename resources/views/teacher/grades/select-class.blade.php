@extends('layouts.app')

@section('title', 'Choisir une classe')
@section('page_title', 'Gestion des Notes')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center border-b pb-3">
            <i class="fas fa-star text-primary mr-2"></i> Sélectionnez une classe pour gérer les notes
        </h3>
        
        @if($assignments->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($assignments as $assignment)
                    <a href="{{ route('teacher.grades.index', ['classId' => $assignment->school_class_id]) }}" 
                       class="block bg-gradient-to-br from-purple-50 to-white border border-purple-200 rounded-xl p-6 hover:shadow-lg hover:border-purple-400 transition group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-purple-100 text-purple-700 p-3 rounded-full group-hover:scale-110 transition">
                                <i class="fas fa-book-open text-xl"></i>
                            </div>
                            @if($assignment->is_main_teacher)
                                <span class="px-3 py-1 bg-purple-600 text-white text-xs font-bold rounded-full">★ Titulaire</span>
                            @else
                                <span class="px-3 py-1 bg-gray-300 text-gray-700 text-xs font-bold rounded-full">Adjoint(e)</span>
                            @endif
                        </div>
                        <h4 class="text-xl font-bold text-gray-800 mb-2">{{ $assignment->schoolClass->name }}</h4>
                        <p class="text-sm text-gray-600 mb-4">{{ ucfirst($assignment->schoolClass->cycle ?? '') }} - Niveau {{ $assignment->schoolClass->level ?? '' }}</p>
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-users mr-2 text-purple-600"></i>
                                <span class="font-semibold">{{ $assignment->schoolClass->students_count ?? 0 }} élèves</span>
                            </div>
                            <div class="text-purple-600 group-hover:translate-x-1 transition">
                                <i class="fas fa-arrow-right text-xl"></i>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-chalkboard text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg font-semibold">Aucune classe assignée</p>
            </div>
        @endif
    </div>
</div>
@endsection