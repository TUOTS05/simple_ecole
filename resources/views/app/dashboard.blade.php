@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Tableau de bord')

@push('styles')
<style>
    .kpi-card {
        transition: transform 0.2s;
    }
    .kpi-card:hover {
        transform: translateY(-4px);
    }
</style>
@endpush

@section('content')
    
    <!-- En-tête -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            Bienvenue, {{ auth()->user()->first_name }} 👋
        </h1>
        <p class="text-gray-600 mt-2">
            Voici un aperçu de votre école <strong>{{ session('current_school')->name }}</strong>
        </p>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- KPIs PRINCIPAUX -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total Élèves -->
        <div class="kpi-card bg-white rounded-lg shadow-lg p-6 border-l-4 border-primary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Élèves actifs</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalStudents }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $totalClasses }} classes</p>
                </div>
                <div class="text-5xl opacity-20">👨‍🎓</div>
            </div>
        </div>
        
                <!-- Nombre d'enseignants -->
        <div class="kpi-card bg-white rounded-lg shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Enseignants</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalTeachers }}</p>
                    <p class="text-xs text-gray-500 mt-1">Personnel actif</p>
                </div>
                <div class="text-5xl opacity-20">👨‍🏫</div>
            </div>
        </div>
        
        <!-- Taux de recouvrement -->
        <div class="kpi-card bg-white rounded-lg shadow-lg p-6 border-l-4 border-secondary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Recouvrement</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $collectionRate }}%</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ number_format($totalTuitionPaid / 1000000, 1) }}M / {{ number_format($totalTuitionExpected / 1000000, 1) }}M
                    </p>
                </div>
                <div class="text-5xl opacity-20">💰</div>
            </div>
        </div>
        
        <!-- Taux de présence -->
        <div class="kpi-card bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Présence (7j)</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $attendanceRate }}%</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $presentCount }} présents</p>
                </div>
                <div class="text-5xl opacity-20">✅</div>
            </div>
        </div>
        
    </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- RÉPARTITION DES ÉLÈVES PAR CLASSE -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="mr-2">📊</span> Répartition des élèves par classe
        </h2>
        
        <div class="space-y-4">
            @php
                $maxStudents = $studentsPerClass->max('count') ?: 1;
            @endphp
            
            @foreach($studentsPerClass as $class)
                <div class="flex items-center">
                    <div class="w-32 text-sm font-semibold text-gray-700">{{ $class['name'] }}</div>
                    <div class="flex-1 mx-4">
                        <div class="w-full bg-gray-200 rounded-full h-6 relative overflow-hidden">
                            <div class="bg-primary h-6 rounded-full flex items-center justify-end pr-2 transition-all duration-500" 
                                 style="width: {{ ($class['count'] / $maxStudents) * 100 }}%">
                                @if($class['count'] > 0)
                                    <span class="text-white text-xs font-bold">{{ $class['count'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="w-16 text-right text-sm text-gray-600">
                        {{ $class['count'] }} élève{{ $class['count'] > 1 ? 's' : '' }}
                    </div>
                </div>
            @endforeach
            
            @if($studentsPerClass->isEmpty())
                <p class="text-center text-gray-500 py-4">Aucune classe configurée</p>
            @endif
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- STATISTIQUES FINANCIÈRES -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Résumé financier -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">💵</span> Situation financière
            </h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Total attendu</span>
                    <span class="text-2xl font-bold text-gray-800">
                        {{ number_format($totalTuitionExpected, 0, ',', ' ') }} FCFA
                    </span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Total encaissé</span>
                    <span class="text-2xl font-bold text-accent">
                        {{ number_format($totalTuitionPaid, 0, ',', ' ') }} FCFA
                    </span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Reste à percevoir</span>
                    <span class="text-2xl font-bold text-danger">
                        {{ number_format($totalTuitionRemaining, 0, ',', ' ') }} FCFA
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Frais d'inscription payés</span>
                    <span class="text-lg font-semibold">
                        {{ $registrationPaidCount }} / {{ $totalEnrollments }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Graphique : Paiements par mois -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">📊</span> Évolution des paiements (6 mois)
            </h2>
            <canvas id="paymentsChart" height="100"></canvas>
        </div>
        
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- GRAPHIQUES AVANCÉS -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Graphique : Répartition des statuts de paiement -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">🥧</span> Statut des paiements
            </h2>
            <canvas id="paymentStatusChart"></canvas>
        </div>

        <!-- Graphique : Présences par jour -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">📈</span> Présences (7 derniers jours)
            </h2>
            <canvas id="attendanceChart"></canvas>
        </div>
        
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
       <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- ALERTES -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    
    @if($lateInstallments->count() > 0 || $recentAbsences->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <!-- Paiements en retard -->
            @if($lateInstallments->count() > 0)
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-red-500">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <span class="mr-2">⚠️</span> Échéances en retard
                    </h2>
                    <div class="space-y-3">
                        @foreach($lateInstallments as $installment)
                            @php
                                $student = $installment->enrollment->student;
                                $reste = $installment->amount - $installment->paid_amount;
                            @endphp
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                                <div>
                                    <p class="font-semibold text-gray-800">
                                        {{ $student->last_name ?? 'Inconnu' }} {{ $student->first_name ?? '' }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ $installment->description }} (Échu le {{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }})
                                    </p>
                                    <p class="text-sm font-bold text-red-600 mt-1">
                                        Reste : {{ number_format($reste, 0, ',', ' ') }} FCFA
                                    </p>
                                </div>
                                <a href="{{ route('app.payments.create', ['student_id' => $student->id, 'school_year_id' => $installment->enrollment->school_year_id]) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-semibold bg-blue-100 px-3 py-1 rounded">
                                    Encaisser →
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Absences récentes -->
            @if($recentAbsences->count() > 0)
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-yellow-500">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <span class="mr-2">🚨</span> Absences récentes
                    </h2>
                    <div class="space-y-3">
                        @foreach($recentAbsences as $student)
                            <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                                <div>
                                    <p class="font-semibold text-gray-800">
                                        {{ $student->last_name }} {{ $student->first_name }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Matricule : {{ $student->matricule }}
                                    </p>
                                </div>
                                <a href="{{ route('app.students.show', $student->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                    Voir →
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
        </div>
    @endif
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- ACTIONS RAPIDES -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="mr-2">⚡</span> Actions rapides
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('app.students.create') }}" 
               class="flex flex-col items-center p-4 bg-primary hover:bg-primary-dark text-white rounded-lg transition">
                <span class="text-3xl mb-2">👨‍🎓</span>
                <span class="text-sm font-semibold">Nouvel élève</span>
            </a>
            <a href="{{ route('app.attendances.create') }}" 
               class="flex flex-col items-center p-4 bg-accent hover:bg-green-600 text-white rounded-lg transition">
                <span class="text-3xl mb-2">✅</span>
                <span class="text-sm font-semibold">Faire l'appel</span>
            </a>
            <a href="{{ route('app.payments.create') }}" 
               class="flex flex-col items-center p-4 bg-secondary hover:bg-yellow-400 text-gray-800 rounded-lg transition">
                <span class="text-3xl mb-2">💰</span>
                <span class="text-sm font-semibold">Encaisser</span>
            </a>
            <a href="{{ route('app.enrollments.create') }}" 
               class="flex flex-col items-center p-4 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition">
                <span class="text-3xl mb-2">📝</span>
                <span class="text-sm font-semibold">Inscrire</span>
            </a>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // ═══════════════════════════════════════════════════════════════
    // GRAPHIQUE 1 : Paiements par mois
    // ═══════════════════════════════════════════════════════════════
    
    const paymentLabels = @json($paymentLabels);
    const paymentData = @json($paymentData);
    
    new Chart(document.getElementById('paymentsChart'), {
        type: 'bar',
        data: {
            labels: paymentLabels,
            datasets: [{
                label: 'Paiements (FCFA)',
                data: paymentData,
                backgroundColor: 'rgba(135, 206, 235, 0.8)',
                borderColor: 'rgba(135, 206, 235, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000) + 'K';
                        }
                    }
                }
            }
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // GRAPHIQUE 2 : Répartition des statuts de paiement
    // ═══════════════════════════════════════════════════════════════
    
    const paymentStatusCounts = @json($paymentStatusCounts);
    
    new Chart(document.getElementById('paymentStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'Partiel', 'Complet'],
            datasets: [{
                data: [
                    paymentStatusCounts.pending || 0,
                    paymentStatusCounts.partial || 0,
                    paymentStatusCounts.completed || 0
                ],
                backgroundColor: [
                    'rgba(239, 154, 154, 0.8)',
                    'rgba(255, 249, 196, 0.8)',
                    'rgba(165, 214, 167, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // GRAPHIQUE 3 : Présences par jour
    // ═══════════════════════════════════════════════════════════════
    
    const attendanceLabels = @json($attendanceLabels);
    const attendancePresent = @json($attendancePresent);
    const attendanceAbsent = @json($attendanceAbsent);
    const attendanceLate = @json($attendanceLate);
    
    new Chart(document.getElementById('attendanceChart'), {
        type: 'line',
        data: {
            labels: attendanceLabels,
            datasets: [
                {
                    label: 'Présents',
                    data: attendancePresent,
                    borderColor: 'rgba(165, 214, 167, 1)',
                    backgroundColor: 'rgba(165, 214, 167, 0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Absents',
                    data: attendanceAbsent,
                    borderColor: 'rgba(239, 154, 154, 1)',
                    backgroundColor: 'rgba(239, 154, 154, 0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Retards',
                    data: attendanceLate,
                    borderColor: 'rgba(255, 249, 196, 1)',
                    backgroundColor: 'rgba(255, 249, 196, 0.2)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stacked: false
                }
            }
        }
    });


        // GRAPHIQUE 2 : Répartition des statuts de paiement
    const paymentStatusCounts = @json($paymentStatusCounts);
    
    new Chart(document.getElementById('paymentStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Payé', 'En attente', 'Partiel', 'En retard'],
            datasets: [{
                data: [
                    paymentStatusCounts.paid || 0,
                    paymentStatusCounts.pending || 0,
                    paymentStatusCounts.partial || 0,
                    paymentStatusCounts.overdue || 0
                ],
                backgroundColor: [
                    'rgba(165, 214, 167, 0.8)', // Vert (Payé)
                    'rgba(255, 249, 196, 0.8)', // Jaune (En attente)
                    'rgba(255, 200, 100, 0.8)', // Orange (Partiel)
                    'rgba(239, 154, 154, 0.8)'  // Rouge (En retard)
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
@endpush