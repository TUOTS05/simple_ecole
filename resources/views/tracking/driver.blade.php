<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Partage de position — {{ $vehicle->plate_number }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('icons/log.jpg.jpeg') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg p-6" x-data="tracker()" x-init="init()">
        <div class="text-center mb-6">
            <span class="text-5xl">🚌</span>
            <h1 class="text-xl font-bold text-gray-800 mt-2">{{ $vehicle->plate_number }}</h1>
            <p class="text-sm text-gray-500">{{ $vehicle->driver_name ?? 'Chauffeur' }}</p>
        </div>

        <div class="rounded-xl border-2 p-4 mb-4 text-center transition"
             :class="active ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50'">
            <p class="text-sm font-semibold" :class="active ? 'text-green-700' : 'text-gray-500'" x-text="statusLabel"></p>
            <p class="text-xs text-gray-500 mt-1" x-show="lastSentAt" x-text="'Dernier envoi : ' + lastSentAt"></p>
        </div>

        <button @click="active ? stop() : start()"
            class="w-full py-4 rounded-xl font-bold text-white text-lg transition"
            :class="active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'">
            <span x-show="!active">▶️ Démarrer le partage</span>
            <span x-show="active">⏹️ Arrêter le partage</span>
        </button>

        <p class="text-xs text-gray-400 text-center mt-4">
            Laissez cette page ouverte pendant le trajet. Votre position n'est visible que par l'établissement et les parents des élèves affectés à ce véhicule.
        </p>

        <p x-show="errorMessage" x-text="errorMessage" class="text-sm text-red-600 text-center mt-3 bg-red-50 rounded-lg p-3"></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function tracker() {
            return {
                active: false,
                watchId: null,
                lastSentAt: null,
                lastSentTime: 0,
                errorMessage: '',
                statusLabel: 'Partage inactif',

                init() {
                    if (! navigator.geolocation) {
                        this.errorMessage = "Ce navigateur ne permet pas de partager la position GPS.";
                    }
                },

                start() {
                    this.errorMessage = '';
                    if (! navigator.geolocation) return;

                    this.watchId = navigator.geolocation.watchPosition(
                        (position) => this.onPosition(position),
                        (error) => this.onError(error),
                        { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 }
                    );
                    this.active = true;
                    this.statusLabel = 'Partage actif — en attente du premier signal GPS...';
                },

                stop() {
                    if (this.watchId !== null) {
                        navigator.geolocation.clearWatch(this.watchId);
                        this.watchId = null;
                    }
                    this.active = false;
                    this.statusLabel = 'Partage arrêté';
                },

                onPosition(position) {
                    const now = Date.now();
                    // Envoie au maximum une position toutes les 15 secondes, même si le
                    // téléphone remonte des positions plus fréquemment.
                    if (now - this.lastSentTime < 15000) return;
                    this.lastSentTime = now;

                    const speedKmh = position.coords.speed !== null ? Math.round(position.coords.speed * 3.6 * 10) / 10 : null;

                    fetch("{{ route('vehicle-tracking.ping', $vehicle->tracking_token ?? '') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            speed_kmh: speedKmh,
                        }),
                    }).then((response) => {
                        if (response.ok) {
                            this.lastSentAt = new Date().toLocaleTimeString('fr-FR');
                            this.statusLabel = 'Partage actif';
                        }
                    }).catch(() => {
                        this.statusLabel = 'Partage actif — problème réseau, nouvel essai automatique...';
                    });
                },

                onError(error) {
                    this.active = false;
                    this.errorMessage = error.code === error.PERMISSION_DENIED
                        ? "Autorisation refusée : activez la localisation pour ce site dans les réglages de votre téléphone."
                        : "Impossible d'obtenir votre position (" + error.message + ").";
                },
            };
        }
    </script>
</body>

</html>
