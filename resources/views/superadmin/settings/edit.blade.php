@extends('layouts.app')

@section('title', 'Paramètres Système')
@section('page_title', 'Paramètres Système')

@section('content')
    <div class="max-w-4xl mx-auto">
        
        <!-- Messages de succès/erreur -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('superadmin.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="space-y-8">
                
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 1 : INFORMATIONS GÉNÉRALES -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">🏢</span> Informations générales
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nom de la plateforme -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom de la plateforme *</label>
                            <input type="text" name="platform_name" value="{{ old('platform_name', $settings->platform_name) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('platform_name') border-red-500 @enderror">
                            @error('platform_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Logo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo de la plateforme</label>
                            @if($settings->logo)
                                <div class="mb-3">
                                    <img src="{{ $settings->logo_url }}" alt="Logo actuel" class="h-16 object-contain">
                                </div>
                            @endif
                            <input type="file" name="logo" accept="image/png, image/jpeg, image/jpg"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('logo') border-red-500 @enderror">
                            @error('logo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-500 mt-1">Max 2MB, formats: JPG, PNG</p>
                        </div>

                        <!-- Favicon -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                            @if($settings->favicon)
                                <div class="mb-3">
                                    <img src="{{ $settings->favicon_url }}" alt="Favicon actuel" class="h-10 object-contain">
                                </div>
                            @endif
                            <input type="file" name="favicon" accept="image/ico, image/png, image/jpeg"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('favicon') border-red-500 @enderror">
                            @error('favicon') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-500 mt-1">Max 1MB, formats: ICO, PNG, JPG</p>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 2 : SUPPORT ET CONTACT -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">📞</span> Support et Contact
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Email de support -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email de support</label>
                            <input type="email" name="support_email" value="{{ old('support_email', $settings->support_email) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('support_email') border-red-500 @enderror">
                            @error('support_email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Téléphone de support -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone de support</label>
                            <input type="tel" name="support_phone" value="{{ old('support_phone', $settings->support_phone) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('support_phone') border-red-500 @enderror">
                            @error('support_phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Adresse de support -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Adresse du support</label>
                            <textarea name="support_address" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('support_address') border-red-500 @enderror">{{ old('support_address', $settings->support_address) }}</textarea>
                            @error('support_address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 3 : APPARENCE -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">🎨</span> Apparence
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Couleur principale -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Couleur principale</label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="primary_color" value="{{ old('primary_color', $settings->primary_color) }}"
                                       class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                                <input type="text" name="primary_color_text" value="{{ old('primary_color', $settings->primary_color) }}"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Utilisée pour les boutons et liens principaux</p>
                        </div>

                        <!-- Couleur secondaire -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Couleur secondaire</label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="secondary_color" value="{{ old('secondary_color', $settings->secondary_color) }}"
                                       class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                                <input type="text" name="secondary_color_text" value="{{ old('secondary_color', $settings->secondary_color) }}"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Utilisée pour les accents et succès</p>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 4 : LÉGALE -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">📄</span> Documents légaux
                    </h3>
                    <div class="space-y-6">
                        <!-- Conditions d'utilisation -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Conditions d'utilisation</label>
                            <textarea name="terms_of_service" rows="6"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('terms_of_service') border-red-500 @enderror">{{ old('terms_of_service', $settings->terms_of_service) }}</textarea>
                            @error('terms_of_service') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Politique de confidentialité -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Politique de confidentialité</label>
                            <textarea name="privacy_policy" rows="6"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('privacy_policy') border-red-500 @enderror">{{ old('privacy_policy', $settings->privacy_policy) }}</textarea>
                            @error('privacy_policy') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 5 : MODE MAINTENANCE -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">🔧</span> Mode Maintenance
                    </h3>
                    <div class="space-y-4">
                        <!-- Checkbox maintenance -->
                        <div>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="maintenance_mode" value="1" {{ old('maintenance_mode', $settings->maintenance_mode) ? 'checked' : '' }}
                                       class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <span class="ml-2 text-sm font-medium text-gray-700">Activer le mode maintenance</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1 ml-6">⚠️ Bloque l'accès à tous les utilisateurs sauf le Super Admin</p>
                        </div>

                        <!-- Message de maintenance -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message de maintenance</label>
                            <textarea name="maintenance_message" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('maintenance_message') border-red-500 @enderror"
                                      placeholder="Ex: Nous effectuons une maintenance programmée. Le service sera de retour dans quelques minutes.">{{ old('maintenance_message', $settings->maintenance_message) }}</textarea>
                            @error('maintenance_message') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Boutons -->
            <div class="mt-8 flex justify-end space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-semibold transition shadow-md">
                    💾 Enregistrer les paramètres
                </button>
            </div>
            
        </form>
        
    </div>

    <!-- Script pour synchroniser les color pickers avec les champs texte -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const primaryColor = document.querySelector('input[name="primary_color"]');
            const primaryColorText = document.querySelector('input[name="primary_color_text"]');
            const secondaryColor = document.querySelector('input[name="secondary_color"]');
            const secondaryColorText = document.querySelector('input[name="secondary_color_text"]');

            primaryColor.addEventListener('input', function() {
                primaryColorText.value = this.value;
            });

            primaryColorText.addEventListener('input', function() {
                if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                    primaryColor.value = this.value;
                }
            });

            secondaryColor.addEventListener('input', function() {
                secondaryColorText.value = this.value;
            });

            secondaryColorText.addEventListener('input', function() {
                if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                    secondaryColor.value = this.value;
                }
            });
        });
    </script>
@endsection