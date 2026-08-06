@extends('layouts.app')

@section('title', 'Assignation des Enseignants')
@section('page_title', 'Personnel Enseignant')

@section('content')
    
    @if(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif
    
    @if($errors->any())
        <div class="bg-danger text-white px-6 py-4 rounded-lg mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Assignation des Enseignants</h1>
            <p class="text-gray-600 mt-1">Année scolaire : <strong>{{ $currentYear->name }}</strong></p>
        </div>
        <a href="{{ route('app.teacher-assignments.create') }}" 
           class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Assigner un enseignant
        </a>
    </div>

    <!-- Grille des classes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($classes as $class)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-primary">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">{{ $class->name }}</h3>
                        <p class="text-sm text-gray-500">{{ ucfirst($class->cycle) }} - {{ $class->level }}</p>
                    </div>
                    <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                        {{ $assignments->has($class->id) ? $assignments[$class->id]->count() : 0 }} enseignant(s)
                    </span>
                </div>

                <div class="space-y-3">
                    @if($assignments->has($class->id))
                        @foreach($assignments[$class->id] as $assignment)
                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                <div>
                                    <p class="font-semibold text-gray-800">
                                        {{ $assignment->teacher->first_name }} {{ $assignment->teacher->last_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        @if($assignment->is_main_teacher)
                                            <span class="text-primary font-bold">★ Titulaire</span>
                                        @else
                                            <span class="text-gray-600">Adjoint(e)</span>
                                        @endif
                                    </p>
                                </div>
                                <form action="{{ route('app.teacher-assignments.destroy', $assignment) }}" method="POST" 
                                      onsubmit="return confirm('Retirer cet enseignant de la classe ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-semibold">
                                        ✕ Retirer
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-400 italic text-center py-2">Aucun enseignant assigné</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

@endsection