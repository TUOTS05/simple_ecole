<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fichiers personnels (photos d'enfants, de personnes autorisées, documents d'inscription) :
 * stockés sur le disque privé « local » (storage/app/private), jamais accessibles par URL
 * directe. Ils sont servis par une route qui vérifie d'abord les droits de l'utilisateur.
 *
 * Les fichiers déposés avant ce changement sont encore sur le disque « public » : ils restent
 * lisibles ici jusqu'à leur déplacement par la commande files:secure-students.
 */
class PrivateFiles
{
    public const DISK = 'local';

    public const LEGACY_DISK = 'public';

    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
        Storage::disk(self::LEGACY_DISK)->delete($path);
    }

    /** Disque qui contient réellement le fichier (privé en priorité), ou null s'il n'existe pas. */
    public static function diskFor(?string $path): ?string
    {
        if (! $path || str_contains($path, '..')) {
            return null;
        }

        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    /** Chemin absolu sur le serveur (génération de PDF), ou null. */
    public static function absolutePath(?string $path): ?string
    {
        $disk = self::diskFor($path);

        return $disk ? Storage::disk($disk)->path($path) : null;
    }

    /** Réponse HTTP affichant le fichier dans le navigateur, sans mise en cache partagée. */
    public static function response(?string $path, ?string $downloadName = null): StreamedResponse
    {
        $disk = self::diskFor($path);
        abort_unless($disk, 404);

        return Storage::disk($disk)->response($path, $downloadName, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Déplace un fichier du disque public vers le disque privé. Retourne true s'il a été déplacé. */
    public static function secure(?string $path): bool
    {
        if (! $path || str_contains($path, '..')) {
            return false;
        }

        $public = Storage::disk(self::LEGACY_DISK);
        if (! $public->exists($path)) {
            return false;
        }

        $private = Storage::disk(self::DISK);
        if (! $private->exists($path)) {
            $private->writeStream($path, $public->readStream($path));
        }
        $public->delete($path);

        return true;
    }
}
