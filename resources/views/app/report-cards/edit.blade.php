@extends('layouts.app')

@section('title', 'Modifier les notes')
@section('page_title', 'Modifier les notes')

@section('content')
    
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('app.report-cards.show', $reportCard) }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour au bulletin
            </a>
        </div>

        <!-- Informations du bulletin -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-primary">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Élève</p>
                    <p class="font-bold text-lg">{{ $reportCard->student->last_name }} {{ $reportCard->student->first_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Classe</p>
                    <p class="font-bold">{{ $reportCard->schoolClass->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Période</p>
                    <p class="font-bold">
                        @if($reportCard->period === 'mensuel')
                            Mensuel - {{ \Carbon\Carbon::parse($reportCard->month)->format('F Y') }}
                        @else
                            {{ $reportCard->quarter }}ème Trimestre
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Moyenne actuelle</p>
                    <p class="font-bold text-2xl text-primary">{{ number_format($reportCard->average, 2) }}/20</p>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <ul class="list-disc list-inside text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulaire de modification -->
        <form action="{{ route('app.report-cards.update', $reportCard) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <span class="mr-2">✏️</span> Modifier les notes
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ $subjects->count() }} matières
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-3 py-3 text-left text-sm font-semibold text-gray-700" style="width: 40%;">
                                    Matière
                                </th>
                                <th class="border border-gray-300 px-3 py-3 text-center text-sm font-semibold text-gray-700" style="width: 15%;">
                                    Coefficient
                                </th>
                                <th class="border border-gray-300 px-3 py-3 text-center text-sm font-semibold text-gray-700" style="width: 20%;">
                                    Note /100
                                </th>
                                <th class="border border-gray-300 px-3 py-3 text-center text-sm font-semibold text-gray-700" style="width: 25%;">
                                    Appréciation
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjects as $subject)
                                @php
                                    $existingGrade = $existingGrades->get($subject->id);
                                    $currentScore = $existingGrade ? $existingGrade->score : 0;
                                    $currentRemarks = $existingGrade ? $existingGrade->remarks : '';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-3 py-3 font-semibold">
                                        {{ $subject->name }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-3 text-center">
                                        {{ $subject->coefficient }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-3">
                                        <div class="flex justify-center">
                                            <input type="number" 
                                                   name="grades[{{ $subject->id }}][score]" 
                                                   value="{{ $currentScore }}"
                                                   min="0" 
                                                   max="100" 
                                                   step="0.25"
                                                   required
                                                   class="w-24 px-2 py-1 border border-gray-300 rounded text-center text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                            <input type="hidden" 
                                                   name="grades[{{ $subject->id }}][max_score]" 
                                                   value="100">
                                        </div>
                                    </td>
                                    <td class="border border-gray-300 px-3 py-3">
                                        <input type="text" 
                                               name="grades[{{ $subject->id }}][remarks]" 
                                               value="{{ $currentRemarks }}"
                                               placeholder="Appréciation" 
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm text-center">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mb-6">
                <a href="{{ route('app.report-cards.show', $reportCard) }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                    Annuler
                </a>
                <button type="submit" class="bg-gradient-to-r from-primary to-primary-dark text-white px-8 py-3 rounded-lg font-bold text-lg shadow-lg transition transform hover:scale-105">
                    💾 Mettre à jour les notes
                </button>
            </div>
        </form>

    </div>
    
@endsection