<x-guest-layout>
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Content de vous revoir</h1>
        <p class="text-sm text-gray-500 mt-1">Connectez-vous pour accéder à votre espace</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Adresse email" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="vous@ecole.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Mot de passe" />
                @if (Route::has('password.request'))
                    <a class="text-sm text-primary hover:text-primary-dark font-medium" href="{{ route('password.request') }}">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="block mt-1.5 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary" name="remember">
                <span class="ms-2 text-sm text-gray-600">Se souvenir de moi</span>
            </label>
        </div>

        <x-primary-button class="w-full justify-center py-3 text-sm">
            Se connecter
        </x-primary-button>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-100 text-center space-y-2">
        <p class="text-sm text-gray-500">
            Pas encore de compte ?
            <a href="{{ route('request-account') }}" class="text-primary hover:text-primary-dark font-semibold">Demander mon compte</a>
        </p>
        <p class="text-sm text-gray-500">
            <a href="{{ route('demo.login') }}" class="text-gray-400 hover:text-gray-600">Essayer la démo en direct →</a>
        </p>
    </div>
</x-guest-layout>
