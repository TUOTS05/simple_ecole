@extends('layouts.app')

@section('title', $class->name)
@section('page_title', $class->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- En-tête de la classe -->
    <div class="bg-gradient-to-r from-primary to-blue-600 text-white rounded-2xl p-6 shadow-lg flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ $class->name }}</h2>
            <p class="text-blue-100">{{ ucfirst($class->cycle ?? '') }} - Niveau {{ $class->level ?? '' }}</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center space-x-4 text-sm bg-white/20 px-4 py-2 rounded-lg">
            <span><i class="fas fa-users mr-1"></i> {{ $students->count() }} élèves</span>
        </div>
    </div>

    <!-- ✅ BOUTONS D'ACTION EN HAUT (Alignés à droite, taille réduite) -->
    <div class="flex justify-end gap-3 mb-4">
        <a href="{{ route('teacher.attendance.create', ['classId' => $class->id]) }}" class="flex items-center px-4 py-2 bg-accent hover:bg-green-400 text-gray-800 rounded-lg font-semibold text-sm transition shadow-sm">
            <i class="fas fa-clipboard-check mr-2"></i> Faire l'appel
        </a>
        <a href="{{ route('teacher.attendance.history', ['classId' => $class->id]) }}" class="flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 rounded-lg font-semibold text-sm transition shadow-sm">
            <i class="fas fa-clock-rotate-left mr-2"></i> Historique
        </a>
        <a href="{{ route('teacher.grades.index', ['classId' => $class->id]) }}" class="flex items-center px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-800 border border-purple-300 rounded-lg font-semibold text-sm transition shadow-sm">
            <i class="fas fa-star mr-2"></i> Gérer les notes
        </a>
    </div>

    <!-- Liste des élèves -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-users text-primary mr-2"></i> Liste des élèves
            </h3>
        </div>

        @if($students->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-600 text-sm uppercase">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Matricule</th>
                        <th class="px-6 py-4 font-semibold">Nom et Prénom</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($students as $student)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ $student->matricule ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-800">{{ $student->last_name }} {{ $student->first_name }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="#" class="text-primary hover:text-primary-dark text-sm font-semibold">Voir le dossier →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12">
            <p class="text-gray-500">Aucun élève actif dans cette classe</p>
        </div>
        @endif
    </div>
</div>
@endsection