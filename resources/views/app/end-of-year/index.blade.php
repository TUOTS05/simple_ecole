@extends('layouts.app')

@section('content')
<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            🎓 Décisions de Fin d'Année & Passage
        </h2>
        <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm font-semibold">
            Année scolaire : {{ $currentYear->name ?? 'Non définie' }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($classes as $class)
            <a href="{{ route('app.end-of-year.show', $class->id) }}" class="block p-6 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-primary transition-all duration-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">{{ $class->name }}</h3>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $class->cycle === 'primaire' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($class->cycle ?? 'Général') }}
                    </span>
                </div>
                <div class="flex items-center text-gray-600 text-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span class="font-semibold">{{ $class->students_count }}</span> &nbsp;élèves inscrits
                </div>
                <div class="mt-4 text-sm text-primary font-medium flex items-center">
                    Gérer les passages →
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-10 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                Aucune classe trouvée pour l'année scolaire en cours.
            </div>
        @endforelse
    </div>
</div>
@endsection