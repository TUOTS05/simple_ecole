<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    /**
     * Afficher le formulaire de modification des paramètres
     */
    public function edit()
    {
        $settings = SystemSetting::getSettings();
        return view('superadmin.settings.edit', compact('settings'));
    }

    /**
     * Mettre à jour les paramètres système
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'platform_name' => 'required|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'support_address' => 'nullable|string',
            'terms_of_service' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'maintenance_mode' => 'boolean',
            'maintenance_message' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png,jpg|max:1024',
        ]);

        // Récupérer ou créer les paramètres (singleton)
        $settings = SystemSetting::firstOrNew();

        // Gérer l'upload du logo
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo s'il existe
            if ($settings->logo) {
                Storage::disk('public')->delete($settings->logo);
            }
            $validated['logo'] = $request->file('logo')->store('settings', 'public');
        } else {
            unset($validated['logo']);
        }

        // Gérer l'upload du favicon
        if ($request->hasFile('favicon')) {
            // Supprimer l'ancien favicon s'il existe
            if ($settings->favicon) {
                Storage::disk('public')->delete($settings->favicon);
            }
            $validated['favicon'] = $request->file('favicon')->store('settings', 'public');
        } else {
            unset($validated['favicon']);
        }

        // Gérer le checkbox maintenance_mode
        $validated['maintenance_mode'] = $request->has('maintenance_mode');

        // Mettre à jour les paramètres
        $settings->fill($validated);
        $settings->save();

        // Invalider le cache (déjà fait automatiquement par le modèle, mais on force)
        Cache::forget('system_settings_data');

        return redirect()->route('superadmin.settings.edit')
            ->with('success', '✅ Paramètres système mis à jour avec succès !');
    }
}