<?php

namespace App\Http\Controllers;

use App\Models\ExtraVehicle;
use App\Models\ExtraVehicleLocation;
use Illuminate\Http\Request;

/**
 * Accès public (aucun compte utilisateur) pour le chauffeur/accompagnateur qui
 * partage sa position depuis son téléphone, et pour le ping de position
 * lui-même. Sécurisé uniquement par le jeton opaque dans l'URL
 * (ExtraVehicle::tracking_token), sur le même principe qu'un secret de webhook.
 */
class VehicleTrackingController extends Controller
{
    /**
     * Page mobile ouverte par le chauffeur (ou scannée via le QR imprimé par l'admin).
     */
    public function show(string $token)
    {
        $vehicle = ExtraVehicle::where('tracking_token', $token)->firstOrFail();

        return view('tracking.driver', compact('vehicle'));
    }

    /**
     * Reçoit une position GPS depuis la page chauffeur (appelée en JS, en boucle,
     * tant que le partage est actif).
     */
    public function ping(Request $request, string $token)
    {
        $vehicle = ExtraVehicle::where('tracking_token', $token)->first();

        if (! $vehicle) {
            return response()->json(['error' => 'invalid token'], 404);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_kmh' => 'nullable|numeric|min:0',
        ]);

        $now = now();

        ExtraVehicleLocation::create([
            'extra_vehicle_id' => $vehicle->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed_kmh' => $validated['speed_kmh'] ?? null,
            'recorded_at' => $now,
        ]);

        $vehicle->update([
            'last_latitude' => $validated['latitude'],
            'last_longitude' => $validated['longitude'],
            'last_location_at' => $now,
        ]);

        return response()->json(['success' => true]);
    }
}
