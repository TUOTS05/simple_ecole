<!DOCTYPE html>
<html>
<head>
    <title>Déconnexion</title>
</head>
<body>
    <h1>Déconnexion</h1>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Se déconnecter</button>
    </form>
</body>
</html>