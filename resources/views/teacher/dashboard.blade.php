@extends('layouts.app')

@section('title', 'Tableau de bord Enseignant')
@section('page_title', 'Tableau de bord')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <!-- En-tête de bienvenue -->
    <div class="bg-gradient-to-r from-primary to-blue-600 text-white rounded-2xl p-6 shadow-lg flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h2 class="text-2xl font-bold mb-2">Bonjour, {{ auth()->user()->first_name }} 👋</h2>
            <p class="text-blue-100">Année scolaire : <strong>{{ $currentYear->name ?? 'Non définie' }}</strong></p>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-primary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium uppercase">Mes classes</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $assignments->count() }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full"><i class="fas fa-chalkboard text-primary text-2xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-accent">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium uppercase">Total élèves</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalStudents }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full"><i class="fas fa-users text-accent text-2xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-secondary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium uppercase">Appels aujourd'hui</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $todayAttendances }}</p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full"><i class="fas fa-clipboard-check text-yellow-600 text-2xl"></i></div>
            </div>
        </div>
    </div>

    
</div>
@endsection