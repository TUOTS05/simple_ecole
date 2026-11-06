<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Gabarit principal des pages connectées.
 */
class AppLayout extends Component
{
    /**
     * Renvoie la vue du composant.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
