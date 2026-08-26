<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Essai expiré - Simple Ecole</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-600 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-10 text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-hourglass-end text-red-600 text-3xl"></i>
        </div>
        <h1 class="text-3xl font-black text-gray-900 mb-3">Votre essai est terminé</h1>
        <p class="text-gray-600 mb-6">
            Les 30 jours d'essai gratuit de <strong>{{ $school->name }}</strong> sont arrivés à expiration.
            Souscrivez à un abonnement pour continuer à utiliser la plateforme.
        </p>
        <a href="#contact" class="block bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-lg font-bold transition mb-3">
            <i class="fas fa-credit-card mr-2"></i>
            Choisir un abonnement
        </a>
        <a href="{{ route('logout') }}" class="block text-gray-600 hover:text-gray-900 font-medium py-2">
            Se déconnecter
        </a>
    </div>
</body>
</html>