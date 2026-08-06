@extends('layouts.app')

@section('title', 'Dossier de ' . $student->first_name . ' ' . $student->last_name)
@section('page_title', 'Dossier de l\'élève')

@section('content')

<div class="max-w-6xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('app.students.index') }}" class="text-primary hover:text-primary-dark font-semibold">
            ← Retour à la liste
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start space-x-6">
            <div>
                @if($student->photo_url)
                <img src="{{ asset('storage/' . $student->photo_url) }}" alt="{{ $student->first_name }}" class="w-32 h-32 rounded-lg object-cover">
                @else
                <div class="w-32 h-32 rounded-lg bg-gray-200 flex items-center justify-center text-5xl text-gray-500">
                    👨‍🎓
                </div>
                @endif
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    {{ $student->first_name }} {{ $student->last_name }}
                </h1>
                <p class="text-gray-600 mb-2">
                    Matricule : <span class="font-mono font-bold text-primary">{{ $student->matricule }}</span>
                </p>
                <p class="text-gray-600 mb-4">
                    Né(e) le {{ $student->birth_date->format('d/m/Y') }} - {{ $student->gender === 'M' ? 'Garçon' : 'Fille' }}
                </p>
                <div class="flex space-x-4">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                            {{ $student->status === 'active' ? 'bg-accent text-white' : 
                               ($student->status === 'inactive' ? 'bg-gray-300 text-gray-700' : 'bg-secondary text-gray-800') }}">
                        @if($student->status === 'active') ✅ Actif
                        @elseif($student->status === 'inactive') Inactif
                        @else Diplômé
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Parcours scolaire</h2>

        @forelse($student->enrollments as $enrollment)
        <div class="border border-gray-200 rounded-lg p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">
                        {{ $enrollment->schoolYear->name }}
                    </h3>
                    <p class="text-gray-600">
                        Classe : {{ $enrollment->schoolClass->name ?? '—' }}
                    </p>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-semibold
                            {{ $enrollment->status === 'enrolled' ? 'bg-accent text-white' : 
                               ($enrollment->status === 'reserved' ? 'bg-secondary text-gray-800' : 'bg-danger text-white') }}">
                    @if($enrollment->status === 'enrolled') ✅ Inscrit
                    @elseif($enrollment->status === 'reserved') 📝 Réservé
                    @else ❌ Retiré
                    @endif
                </span>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm text-gray-600">Frais d'inscription</p>
                    <p class="text-lg font-semibold">
                        @if($enrollment->registration_fee_paid)
                        <span class="text-accent">✅ Payé</span>
                        @else
                        <span class="text-danger">❌ Non payé</span>
                        @endif
                    </p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm text-gray-600">Scolarité payée</p>
                    <p class="text-lg font-semibold text-accent">
                        {{ number_format($enrollment->tuition_fee_paid, 0, ',', ' ') }} FCFA
                    </p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm text-gray-600">Reste à payer</p>
                    <p class="text-lg font-semibold text-danger">
                        {{ number_format($enrollment->tuition_fee_remaining, 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>

            @if($enrollment->payments->count() > 0)
            <div class="mt-4">
                <h4 class="font-semibold text-gray-800 mb-2">Paiements ({{ $enrollment->payments->count() }})</h4>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Type</th>
                            <th class="text-left py-2 px-3">Montant</th>
                            <th class="text-left py-2 px-3">Méthode</th>
                            <th class="text-left py-2 px-3">Reçu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollment->payments as $payment)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="py-2 px-3">
                                {{ $payment->payment_type === 'registration' ? '📝 Inscription' : '📚 Scolarité' }}
                            </td>
                            <td class="py-2 px-3 font-semibold">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                            <td class="py-2 px-3">
                                @if($payment->payment_method === 'cash') 💵 Espèces
                                @elseif($payment->payment_method === 'check') 📄 Chèque
                                @elseif($payment->payment_method === 'transfer') 🏦 Virement
                                @else 📱 Mobile Money
                                @endif
                            </td>
                            <td class="py-2 px-3">
                                <a href="{{ route('app.payments.receipt', $payment) }}" class="text-blue-600 hover:text-blue-800">
                                    📄 Voir
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @empty
        <p class="text-center text-gray-500 py-8">Aucune inscription enregistrée</p>
        @endforelse
    </div>

    <!-- Bulletins -->
    @if(isset($reportCards) && $reportCards->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">📝 Bulletins scolaires</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Période</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Moyenne</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Rang</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportCards as $card)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            @if($card->period === 'mensuel')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                Mensuel - {{ \Carbon\Carbon::parse($card->month)->format('F Y') }}
                            </span>
                            @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                {{ $card->quarter }}ème Trimestre
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-sm">{{ $card->schoolClass->name ?? '—' }}</td>
                        <td class="py-3 px-4 font-bold text-lg
                                @if($card->average >= 16) text-accent
                                @elseif($card->average >= 14) text-primary
                                @elseif($card->average >= 12) text-secondary
                                @else text-danger
                                @endif">
                            {{ number_format($card->average, 2) }}/20
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                {{ $card->rank }}/{{ $card->total_students }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.report-cards.show', $card) }}"
                                    class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                    👁️ Voir
                                </a>
                                <a href="{{ route('app.report-cards.pdf', $card) }}"
                                    class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                    📄 PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Statistiques de présence</h2>

        <div class="grid grid-cols-4 gap-4">
            <div class="bg-accent text-white p-4 rounded-lg text-center">
                <p class="text-3xl font-bold">{{ $student->attendances->where('status', 'present')->count() }}</p>
                <p class="text-sm">Présences</p>
            </div>
            <div class="bg-danger text-white p-4 rounded-lg text-center">
                <p class="text-3xl font-bold">{{ $student->attendances->where('status', 'absent')->count() }}</p>
                <p class="text-sm">Absences</p>
            </div>
            <div class="bg-secondary text-gray-800 p-4 rounded-lg text-center">
                <p class="text-3xl font-bold">{{ $student->attendances->where('status', 'late')->count() }}</p>
                <p class="text-sm">Retards</p>
            </div>
            <div class="bg-primary text-white p-4 rounded-lg text-center">
                <p class="text-3xl font-bold">{{ $student->attendances->where('status', 'excused')->count() }}</p>
                <p class="text-sm">Excusé</p>
            </div>
        </div>
    </div>

</div>

@endsection