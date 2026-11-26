// Service worker désactivé.
//
// L'ancienne version servait les pages depuis le cache du navigateur AVANT le réseau
// (« cache d'abord »), si bien qu'une mise à jour du site pouvait rester invisible.
// Aucune page ne l'enregistre plus ; ce fichier sert uniquement à nettoyer les
// navigateurs qui l'avaient installé : il vide ses caches puis se désinstalle.
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.map(key => caches.delete(key)));
        await self.registration.unregister();
        const clients = await self.clients.matchAll({ type: 'window' });
        clients.forEach(client => client.navigate(client.url));
    })());
});
