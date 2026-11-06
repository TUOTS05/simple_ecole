<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande envoyée - Simple Ecole</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg max-w-lg w-full p-8 text-center">
        <div class="text-5xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-gray-800">Votre demande a bien été transmise</h1>
        <p class="text-gray-600 mt-3">
            Merci ! Notre équipe étudie votre demande et revient vers vous sous 24 heures ouvrées.
        </p>

        <ol class="text-left text-sm text-gray-700 mt-6 space-y-3 bg-gray-50 rounded-xl p-5">
            <li><strong>1.</strong> Nous vérifions les informations de votre établissement.</li>
            <li><strong>2.</strong> Après validation, vous recevez vos identifiants de connexion par e-mail
                @if(session('request_email'))à <strong>{{ session('request_email') }}</strong>@endif.
            </li>
            <li><strong>3.</strong> Vous vous connectez et suivez la mise en route de votre école depuis le tableau de bord.</li>
        </ol>

        <p class="text-xs text-gray-500 mt-4">Pensez à vérifier votre dossier « Courrier indésirable » si l'e-mail n'arrive pas.</p>

        <a href="{{ url('/') }}" class="inline-block mt-6 bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold">Retour à l'accueil</a>
    </div>
</body>
</html>
