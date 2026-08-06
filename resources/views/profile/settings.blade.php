<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Paramètres') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Préférences d'affichage -->
            <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                    Préférences d'affichage
                </h3>

                <form method="POST" action="{{ route('profile.settings.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Langue</label>
                        <select name="language" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="fr" {{ old('language', $user->language ?? 'fr') == 'fr' ? 'selected' : '' }}>Français</option>
                            <option value="en" {{ old('language', $user->language ?? '') == 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thème</label>
                        <select name="theme" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="light" {{ old('theme', $user->theme ?? 'light') == 'light' ? 'selected' : '' }}>Clair</option>
                            <option value="dark" {{ old('theme', $user->theme ?? '') == 'dark' ? 'selected' : '' }}>Sombre</option>
                            <option value="auto" {{ old('theme', $user->theme ?? '') == 'auto' ? 'selected' : '' }}>Automatique</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fuseau horaire</label>
                        <select name="timezone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="Africa/Abidjan" {{ old('timezone', $user->timezone ?? 'Africa/Abidjan') == 'Africa/Abidjan' ? 'selected' : '' }}>Afrique/Abidjan (GMT)</option>
                            <option value="Africa/Dakar" {{ old('timezone', $user->timezone ?? '') == 'Africa/Dakar' ? 'selected' : '' }}>Afrique/Dakar (GMT)</option>
                            <option value="Europe/Paris" {{ old('timezone', $user->timezone ?? '') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris (GMT+1)</option>
                        </select>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition font-semibold">
                            Enregistrer les préférences
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notifications -->
            <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    Notifications
                </h3>

                <form method="POST" action="{{ route('profile.settings.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="notify_email" value="1" 
                                   {{ old('notify_email', $user->notify_email ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <span class="ml-3 text-sm text-gray-700">Recevoir les notifications par email</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="notify_messages" value="1" 
                                   {{ old('notify_messages', $user->notify_messages ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <span class="ml-3 text-sm text-gray-700">Notifications de nouveaux messages</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="notify_payments" value="1" 
                                   {{ old('notify_payments', $user->notify_payments ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <span class="ml-3 text-sm text-gray-700">Notifications de paiements</span>
                        </label>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition font-semibold">
                            Enregistrer les notifications
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>