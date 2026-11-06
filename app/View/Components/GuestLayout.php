<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Gabarit des pages publiques (connexion, inscription).
 */
class GuestLayout extends Component
{
    /**
     * Renvoie la vue du composant.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
