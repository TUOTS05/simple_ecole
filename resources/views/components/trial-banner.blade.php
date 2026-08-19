@php
$user = auth()->user();
$school = \App\Models\School::find(session('current_school_id'));

// Détection infaillible de la démo via l'email de l'utilisateur connecté
$isDemo = $user && $user->email === 'demo@schoolmanager.com';

// Sinon, on vérifie si c'est une vraie école en essai
$isTrial = !$isDemo && $school && method_exists($school, 'isTrialActive') && $school->isTrialActive();

$showBanner = $isDemo || $isTrial;
@endphp

@if($showBanner)
@php
$days = $isDemo ? 99 : $school->trialDaysRemaining();
$isUrgent = !$isDemo && $days <= 7;
@endphp

<div class="{{ $isUrgent ? 'bg-red-600' : 'bg-gradient-to-r from-blue-600 to-green-500' }} text-white px-4 py-2.5 text-sm font-medium flex items-center justify-center">
    <i class="fas fa-{{ $isDemo ? 'flask' : 'gift' }} mr-2"></i>
    
    <!-- Ajout de text-left et flex-1 pour l'alignement à gauche et prendre l'espace disponible -->
    <span class="text-left flex-1">
        @if($isDemo)
            <!-- Texte en rouge spécifiquement pour la démo -->
            <span class="text-white-500">Vous êtes en mode <strong>Démonstration</strong>.</span>
        @else
            Essai gratuit : il vous reste
            <strong>{{ $days }} jour{{ $days > 1 ? 's' : '' }}</strong>.
        @endif
    </span>

    <a href="{{ route('request-account') }}" target="_blank"
        class="ml-3 bg-white {{ $isUrgent ? 'text-red-600' : 'text-blue-600' }} px-3 py-1 rounded-full font-bold hover:bg-gray-100 transition text-xs whitespace-nowrap">
        {{ $isDemo ? 'Demander mon compte →' : 'Passer à un abonnement →' }}
    </a>
</div>
@endif