<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du contrat - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .contract-box {
            max-height: 400px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.7;
            color: #4b5563;
        }
        .contract-box h4 {
            color: #1f2937;
            font-weight: 600;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        .contract-box p {
            margin-bottom: 10px;
        }
        .contract-box ul {
            list-style: disc;
            padding-left: 20px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    
    <div class="max-w-3xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
        
        <!-- HEADER -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-10 text-center">
            <div class="text-5xl mb-3">📜</div>
            <h1 class="text-3xl font-bold mb-2">Contrat d'utilisation</h1>
            <p class="text-indigo-100 text-lg">
                Bienvenue <strong>{{ $school->name }}</strong> !<br>
                Veuillez lire et accepter les conditions pour activer votre compte.
            </p>
        </div>

        <!-- CONTENT -->
        <div class="p-8">
            
            <!-- Messages d'erreur -->
            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                    <p class="font-semibold">⚠️ Erreur</p>
                    @foreach($errors->all() as $error)
                        <p class="text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('school.validate-contract.submit', $school->validation_token) }}" method="POST">
                @csrf
                
                <!-- CONTRAT -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                        <span class="mr-2">📋</span> Conditions générales d'utilisation
                    </h3>
                    
                    <div class="contract-box">
                        <p><strong>Date d'effet :</strong> {{ now()->format('d/m/Y') }}</p>
                        <p><strong>Entre :</strong> {{ config('app.name') }} (ci-après "la Plateforme")</p>
                        <p><strong>Et :</strong> {{ $school->name }} (ci-après "l'École")</p>

                        <h4>1. Objet du contrat</h4>
                        <p>Le présent contrat a pour objet de définir les conditions d'utilisation de la plateforme {{ config('app.name') }} par l'École. La plateforme fournit un service en ligne de gestion scolaire (gestion des élèves, enseignants, classes, notes, etc.).</p>

                        <h4>2. Engagement de la Plateforme</h4>
                        <ul>
                            <li>Mettre à disposition un service fiable et sécurisé</li>
                            <li>Assurer la confidentialité des données scolaires</li>
                            <li>Proposer un support technique réactif</li>
                            <li>Garantir la sauvegarde régulière des données</li>
                            <li>Respecter les réglementations en vigueur sur la protection des données</li>
                        </ul>

                        <h4>3. Engagement de l'École</h4>
                        <ul>
                            <li>Utiliser la plateforme de manière conforme à sa destination</li>
                            <li>Ne pas partager ses identifiants de connexion</li>
                            <li>Respecter les droits des utilisateurs (élèves, parents, enseignants)</li>
                            <li>S'acquitter des frais d'abonnement selon le plan choisi</li>
                            <li>Signaler tout incident de sécurité dans les plus brefs délais</li>
                        </ul>

                        <h4>4. Abonnement et facturation</h4>
                        <p>L'École souscrit à un abonnement selon le plan choisi. L'abonnement est valable pour la durée définie lors de la création du compte. À l'expiration, l'accès à la plateforme sera suspendu jusqu'au renouvellement.</p>

                        <h4>5. Protection des données</h4>
                        <p>La plateforme s'engage à protéger les données personnelles conformément aux réglementations en vigueur. Les données scolaires restent la propriété exclusive de l'École et peuvent être exportées à tout moment.</p>

                        <h4>6. Responsabilités</h4>
                        <p>La plateforme ne saurait être tenue responsable des conséquences d'une mauvaise utilisation du service ou du non-respect des présentes conditions par l'École.</p>

                        <h4>7. Résiliation</h4>
                        <p>Chaque partie peut résilier le contrat avec un préavis de 30 jours. En cas de non-paiement ou de violation grave des conditions, la plateforme se réserve le droit de suspendre le service immédiatement.</p>

                        <h4>8. Modification du contrat</h4>
                        <p>La plateforme se réserve le droit de modifier les présentes conditions. Toute modification sera notifiée à l'École par email avec un préavis de 30 jours.</p>

                        <h4>9. Droit applicable</h4>
                        <p>Le présent contrat est soumis au droit en vigueur dans le pays d'exercice de l'École. Tout litige sera soumis aux tribunaux compétents.</p>
                    </div>
                </div>

                <!-- CHECKBOX D'ACCEPTATION -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded mb-6">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="accept_terms" value="1" required
                               class="mt-1 w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="ml-3 text-sm text-gray-800">
                            <strong>J'ai lu et j'accepte les conditions générales d'utilisation</strong> de la plateforme {{ config('app.name') }}. 
                            Je comprends que mon compte sera activé immédiatement après validation.
                        </span>
                    </label>
                </div>

                <!-- BOUTONS -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" 
                            class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold py-4 px-6 rounded-lg transition shadow-lg transform hover:-translate-y-0.5">
                        ✅ Valider le contrat et activer mon compte
                    </button>
                </div>

                <p class="text-center text-xs text-gray-500 mt-4">
                    En validant, vous reconnaissez avoir pris connaissance de l'ensemble des conditions.
                </p>

            </form>
        </div>

        <!-- FOOTER -->
        <div class="bg-gray-50 px-8 py-4 text-center text-xs text-gray-500 border-t">
            © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
        </div>

    </div>

</body>
</html>