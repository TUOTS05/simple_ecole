@extends('layouts.app')

@section('title', 'Inscriptions')
@section('page_title', 'Inscriptions')

@section('content')
    
    @if(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-danger text-white px-6 py-4 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif
    
       <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Inscriptions</h1>
            <p class="text-gray-600 mt-1">Gérez les inscriptions et réservations des élèves</p>
        </div>
        
        <!-- ✅ BOUTONS D'ACTION GROUPÉS -->
        <div class="flex space-x-3">
            <a href="{{ route('app.enrollments.export', request()->query()) }}" 
               class="inline-flex items-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exporter en Excel (CSV)
            </a>
            
            <a href="{{ route('app.enrollments.create') }}" 
               class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                + Nouvelle Inscription
            </a>
        </div>
    </div>
    
        <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('app.enrollments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Nom de l'élève..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Année Scolaire</label>
                <select name="school_year_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Toutes les années</option>
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" {{ request('school_year_id') == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- ✅ NOUVEAU : Filtre par Classe -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                <select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les statuts</option>
                    <option value="reserved" {{ request('status') === 'reserved' ? 'selected' : '' }}>Réservé</option>
                    <option value="enrolled" {{ request('status') === 'enrolled' ? 'selected' : '' }}>Inscrit</option>
                    <option value="withdrawn" {{ request('status') === 'withdrawn' ? 'selected' : '' }}>Retiré</option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2 md:col-span-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    Filtrer
                </button>
                <a href="{{ route('app.enrollments.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
                    Réinitialiser
                </a>
            </div>
            
        </form>
    </div>
    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Année</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Inscription</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Scolarité</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
                       <tbody>
                @forelse($enrollments as $enrollment)
                    @php
                        // Récupérer les échéances de cette inscription
                        $installments = $enrollment->studentInstallments ?? collect();
                        $paidCount = $installments->where('status', 'paid')->count();
                        $totalCount = $installments->count();
                        
                        // Trouver la prochaine échéance non payée (pending, partial ou overdue)
                        $nextPending = $installments
                            ->whereIn('status', ['pending', 'partial', 'overdue'])
                            ->sortBy('due_date')
                            ->first();
                            
                        $isOverdue = $nextPending && \Carbon\Carbon::parse($nextPending->due_date)->isPast();
                    @endphp

                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <!-- Élève -->
                        <td class="py-3 px-4">
                            <div class="font-semibold text-gray-900">
                                {{ $enrollment->student->last_name }} {{ $enrollment->student->first_name }}
                            </div>
                            <div class="text-xs text-gray-500">Mat: {{ $enrollment->student->matricule ?? 'N/A' }}</div>
                        </td>
                        
                        <!-- Année & Classe -->
                        <td class="py-3 px-4 text-sm text-gray-700">{{ $enrollment->schoolYear->name }}</td>
                        <td class="py-3 px-4 text-sm font-medium text-gray-900">
                            {{ $enrollment->schoolClass->name ?? '—' }}
                        </td>
                        
                        <!-- Statut global -->
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $enrollment->status === 'enrolled' ? 'bg-green-100 text-green-800' : 
                                   ($enrollment->status === 'reserved' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                @if($enrollment->status === 'enrolled') ✅ Inscrit
                                @elseif($enrollment->status === 'reserved') 📝 Réservé
                                @else ❌ Retiré
                                @endif
                            </span>
                        </td>
                        
                        <!-- Frais d'inscription -->
                        <td class="py-3 px-4 text-center">
                            @if($enrollment->registration_fee_paid)
                                <span class="inline-flex items-center text-green-600 font-semibold text-sm">✅ Payé</span>
                            @else
                                <span class="inline-flex items-center text-red-600 font-semibold text-sm">❌ En attente</span>
                            @endif
                        </td>
                        
                        <!-- NOUVELLE COLONNE : État des Échéances -->
                        <td class="py-3 px-4">
                            <div class="flex flex-col space-y-2 min-w-[200px]">
                                
                                <!-- 1. Progression globale -->
                                <div class="flex justify-between text-xs font-semibold text-gray-700">
                                    <span>{{ $paidCount }} / {{ $totalCount }} réglées</span>
                                    <span>{{ $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0 }}%</span>
                                </div>
                                
                                <!-- 2. Barre de progression -->
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full transition-all duration-500" 
                                         style="width: {{ $totalCount > 0 ? ($paidCount / $totalCount * 100) : 0 }}%">
                                    </div>
                                </div>

                                <!-- 3. Alerte sur la prochaine échéance -->
                                @if($nextPending)
                                    <div class="mt-1 p-2 rounded-md text-xs {{ $isOverdue ? 'bg-red-50 border border-red-200' : 'bg-orange-50 border border-orange-200' }}">
                                        <div class="flex items-start">
                                            <span class="mr-2 text-lg">{{ $isOverdue ? '⚠️' : '📅' }}</span>
                                            <div>
                                                <div class="font-bold {{ $isOverdue ? 'text-red-700' : 'text-orange-700' }}">
                                                    {{ \Carbon\Carbon::parse($nextPending->due_date)->format('d/m/Y') }}
                                                </div>
                                                <div class="text-gray-600">
                                                    {{ $nextPending->description }}
                                                </div>
                                                <div class="font-bold text-gray-900 mt-1">
                                                    Reste: {{ number_format($nextPending->amount - $nextPending->paid_amount, 0, ',', ' ') }} FCFA
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-1 p-2 rounded-md bg-green-50 border border-green-200 text-xs text-green-700 font-semibold text-center">
                                        ✅ Scolarité entièrement soldée
                                    </div>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.enrollments.show', $enrollment) }}" 
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Voir détails">
                                    👁️
                                </a>
                                <a href="{{ route('app.payments.create', ['enrollment_id' => $enrollment->id, 'student_id' => $enrollment->student_id]) }}" 
                                   class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Enregistrer un paiement">
                                    💰
                                </a>
                                <form action="{{ route('app.enrollments.destroy', $enrollment) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette inscription ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-500">
                            <div class="text-4xl mb-2">📭</div>
                            Aucune inscription trouvée pour ces critères.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($enrollments->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $enrollments->links() }}
            </div>
        @endif
    </div>
    
@endsection