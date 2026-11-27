<?php

namespace App\Console\Commands;

use App\Support\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Déplace les photos et documents d'élèves déposés avant la mise en place du stockage privé
 * (disque public, accessible par URL directe) vers le disque privé, chemins inchangés.
 * On parcourt les dossiers entiers et pas seulement les fiches : des photos envoyées avant
 * la correction du champ « photo » sont sur le disque sans être référencées en base.
 * Sans risque à relancer.
 */
class SecureStudentFiles extends Command
{
    protected $signature = 'files:secure-students';

    protected $description = 'Déplace les photos et documents des élèves vers le stockage privé';

    public const DIRECTORIES = ['students/photos', 'students/documents'];

    public function handle(): int
    {
        $moved = 0;

        foreach (self::DIRECTORIES as $directory) {
            foreach (Storage::disk(PrivateFiles::LEGACY_DISK)->allFiles($directory) as $path) {
                $moved += PrivateFiles::secure($path) ? 1 : 0;
            }
        }

        $this->info("{$moved} fichier(s) déplacé(s) vers le stockage privé.");

        return self::SUCCESS;
    }
}
