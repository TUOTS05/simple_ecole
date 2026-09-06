<?php

// L'hébergement n'a pas l'extension "mbstring", mais le paquet symfony/polyfill-mbstring
// (déjà présent en dépendance) couvre presque toutes les fonctions mb_* utilisées par Laravel —
// sauf mb_strimwidth(), qui n'y est pas incluse. C'est justement celle qu'utilise
// Illuminate\Support\Str::limit() (donc {{ Str::limit(...) }} dans les vues), ce qui provoquait
// une erreur fatale "Call to undefined function ... mb_strimwidth()". On ne peut pas relancer
// "composer install" sur cet hébergement (pas d'accès shell) pour ajouter une dépendance qui la
// couvrirait, donc on la définit nous-mêmes ici, chargée depuis bootstrap/app.php sur chaque
// requête. Si l'extension mbstring est un jour installée par l'hébergeur, cette définition est
// automatiquement ignorée (function_exists) et peut être supprimée sans risque.
if (! function_exists('mb_strimwidth')) {
    function mb_strimwidth($string, $start, $width, $trim_marker = '', $encoding = null)
    {
        $encoding = $encoding ?: 'UTF-8';

        $substr = mb_substr((string) $string, (int) $start, null, $encoding);

        if (mb_strwidth($substr, $encoding) <= $width) {
            return $substr;
        }

        $targetWidth = max(0, $width - mb_strwidth((string) $trim_marker, $encoding));

        $result = '';
        $currentWidth = 0;
        $length = mb_strlen($substr, $encoding);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($substr, $i, 1, $encoding);
            $charWidth = mb_strwidth($char, $encoding);

            if ($currentWidth + $charWidth > $targetWidth) {
                break;
            }

            $result .= $char;
            $currentWidth += $charWidth;
        }

        return $result.$trim_marker;
    }
}
