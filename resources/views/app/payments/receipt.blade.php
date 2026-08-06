<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $payment->enrollment->student->matricule }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    
    <!-- Boutons d'action (non imprimés) -->
    <div class="no-print fixed top-4 right-4 space-x-2">
        <button onclick="window.print()" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            🖨️ Imprimer
        </button>
        <a href="{{ route('app.payments.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
            ← Retour
        </a>
    </div>
    
    <!-- Reçu de paiement -->
    <div class="max-w-3xl mx-auto my-8 bg-white shadow-lg rounded-lg overflow-hidden">
        
        <!-- En-tête -->
        <div class="bg-primary text-white p-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold mb-2">{{ $payment->school->name }}</h1>
                    <p class="text-sm opacity-90">Reçu de paiement</p>
                </div>
                <div class="text-right">
                    <p class="text-sm opacity-90">Reçu N°</p>
                    <p class="text-2xl font-bold">{{ $payment->id }}</p>
                </div>
            </div>
        </div>
        
        <!-- Corps du reçu -->
        <div class="p-8">
            
            <!-- Informations de l'élève -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b border-gray-200 pb-2">Informations de l'élève</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Matricule</p>
                        <p class="text-lg font-bold text-primary">{{ $payment->enrollment->student->matricule }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Nom complet</p>
                        <p class="text-lg font-semibold">{{ $payment->enrollment->student->last_name }} {{ $payment->enrollment->student->first_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Année scolaire</p>
                        <p class="text-lg font-semibold">{{ $payment->enrollment->schoolYear->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Classe</p>
                        <p class="text-lg font-semibold">{{ $payment->enrollment->schoolClass->name ?? '—' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Détails du paiement -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b border-gray-200 pb-2">Détails du paiement</h2>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Type de paiement</p>
                            <p class="text-lg font-semibold">
                                @if($payment->payment_type === 'registration')
                                    📝 Frais d'inscription
                                @else
                                    📚 Frais de scolarité
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Date de paiement</p>
                            <p class="text-lg font-semibold">{{ $payment->payment_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Méthode de paiement</p>
                            <p class="text-lg font-semibold">
                                @if($payment->payment_method === 'cash') 💵 Espèces
                                @elseif($payment->payment_method === 'check') 📄 Chèque
                                @elseif($payment->payment_method === 'transfer') 🏦 Virement
                                @else 📱 Mobile Money
                                @endif
                            </p>
                        </div>
                        @if($payment->reference)
                            <div>
                                <p class="text-sm text-gray-600">Référence</p>
                                <p class="text-lg font-semibold">{{ $payment->reference }}</p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Montant -->
                    <div class="mt-6 pt-6 border-t border-gray-300">
                        <div class="flex justify-between items-center">
                            <p class="text-xl font-bold text-gray-800">Montant payé</p>
                            <p class="text-3xl font-bold text-primary">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notes -->
            @if($payment->notes)
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b border-gray-200 pb-2">Notes</h2>
                    <p class="text-gray-700">{{ $payment->notes }}</p>
                </div>
            @endif
            
            <!-- Signature -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Reçu par</p>
                        <p class="text-lg font-semibold">{{ $payment->receivedBy->first_name }} {{ $payment->receivedBy->last_name }}</p>
                        <p class="text-sm text-gray-500">{{ $payment->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600 mb-2">Signature et cachet</p>
                        <div class="h-20 border border-gray-300 rounded"></div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Pied de page -->
        <div class="bg-gray-50 p-4 text-center text-sm text-gray-600">
            <p>Ce reçu est généré automatiquement par le système EcoleTUO</p>
            <p>{{ $payment->school->name }} - {{ $payment->school->slug }}</p>
        </div>
        
    </div>
    
</body>
</html>