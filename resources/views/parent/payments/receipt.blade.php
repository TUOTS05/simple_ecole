<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <!-- En-tête -->
        <div class="text-center border-b pb-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">REÇU DE PAIEMENT</h1>
            <p class="text-gray-600 mt-2">N° de reçu : <span class="font-mono font-bold">{{ $payment->id }}</span></p>
            <p class="text-gray-600">Date : {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y à H:i') }}</p>
        </div>

        <!-- Informations Élève et École -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Élève</h3>
                <p class="text-lg font-bold text-gray-800">{{ $payment->enrollment->student->first_name }} {{ $payment->enrollment->student->last_name }}</p>
                <p class="text-gray-600">Classe : {{ $payment->enrollment->schoolClass->name ?? 'N/A' }}</p>
                <p class="text-gray-600">Matricule : {{ $payment->enrollment->student->matricule ?? 'N/A' }}</p>
            </div>
            <div class="text-right">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Établissement</h3>
                <p class="text-lg font-bold text-gray-800">{{ $payment->enrollment->school->name ?? 'École' }}</p>
                <p class="text-gray-600">Année : {{ $payment->enrollment->schoolYear->name ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Détails du paiement -->
        <div class="bg-gray-50 p-6 rounded-lg mb-8">
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-700 font-medium">Motif du paiement</span>
                <span class="text-gray-800 font-bold">{{ $payment->description ?? 'Frais de scolarité' }}</span>
            </div>
            <div class="flex justify-between items-center border-t border-gray-200 pt-4">
                <span class="text-xl font-bold text-gray-800">Montant payé</span>
                <span class="text-2xl font-bold text-green-600">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="text-center text-gray-500 text-sm mt-8 pt-6 border-t">
            <p>Ce document sert de preuve de paiement. Merci de le conserver.</p>
            <button onclick="window.print()" class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition no-print">
                🖨️ Imprimer / Sauvegarder en PDF
            </button>
            <a href="{{ url()->previous() }}" class="mt-4 ml-2 px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition no-print">
                ← Retour
            </a>
        </div>
    </div>

    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .shadow-lg { box-shadow: none; }
        }
    </style>
</body>
</html>