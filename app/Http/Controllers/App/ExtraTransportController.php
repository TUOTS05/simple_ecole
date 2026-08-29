<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Extra;
use App\Models\ExtraRoute;
use App\Models\ExtraRouteStop;
use App\Models\ExtraSubscription;
use App\Models\ExtraTransportAssignment;
use App\Models\ExtraVehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExtraTransportController extends Controller
{
    // ==========================================
    // VÉHICULES
    // ==========================================

    public function vehiclesIndex()
    {
        $schoolId = session('current_school_id');
        $vehicles = ExtraVehicle::where('school_id', $schoolId)->orderBy('plate_number')->get();

        return view('app.extras.transport.vehicles', compact('vehicles'));
    }

    public function vehiclesStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'plate_number' => 'required|string|max:30',
            'capacity' => 'required|integer|min:1',
            'driver_name' => 'nullable|string|max:150',
            'driver_phone' => 'nullable|string|max:30',
            'assistant_name' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $validated['school_id'] = $schoolId;
        ExtraVehicle::create($validated);

        return back()->with('success', '✅ Véhicule ajouté avec succès !');
    }

    public function vehiclesUpdate(Request $request, $id)
    {
        $vehicle = ExtraVehicle::where('school_id', session('current_school_id'))->findOrFail($id);

        $validated = $request->validate([
            'plate_number' => 'required|string|max:30',
            'capacity' => 'required|integer|min:1',
            'driver_name' => 'nullable|string|max:150',
            'driver_phone' => 'nullable|string|max:30',
            'assistant_name' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $vehicle->update($validated);

        return back()->with('success', '✅ Véhicule mis à jour avec succès !');
    }

    public function vehiclesDestroy($id)
    {
        $vehicle = ExtraVehicle::where('school_id', session('current_school_id'))->findOrFail($id);

        if ($vehicle->assignments()->exists() || $vehicle->routes()->exists()) {
            return back()->withErrors(['error' => 'Impossible de supprimer un véhicule affecté à un circuit ou des élèves.']);
        }

        $vehicle->delete();

        return back()->with('success', '✅ Véhicule supprimé avec succès !');
    }

    // ==========================================
    // CIRCUITS ET ARRÊTS
    // ==========================================

    public function routesIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $extraId = $request->get('extra_id');

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();
        $vehicles = ExtraVehicle::where('school_id', $schoolId)->where('status', 'active')->orderBy('plate_number')->get();

        $routes = collect();
        if ($extraId) {
            $routes = ExtraRoute::where('extra_id', $extraId)->with('vehicle', 'stops')->orderBy('name')->get();
        }

        return view('app.extras.transport.routes', compact('extras', 'extraId', 'vehicles', 'routes'));
    }

    public function routesStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_id' => 'required|exists:extras,id',
            'name' => 'required|string|max:150',
            'extra_vehicle_id' => 'nullable|exists:extra_vehicles,id',
        ]);

        Extra::where('school_id', $schoolId)->findOrFail($validated['extra_id']);
        ExtraRoute::create($validated);

        return back()->with('success', '✅ Circuit créé avec succès !');
    }

    public function routesDestroy($id)
    {
        $route = ExtraRoute::whereHas('extra', fn ($q) => $q->where('school_id', session('current_school_id')))->findOrFail($id);
        $extraId = $route->extra_id;

        if ($route->assignments()->exists()) {
            return back()->withErrors(['error' => 'Impossible de supprimer un circuit auquel des élèves sont affectés.']);
        }

        $route->delete();

        return redirect()->route('extras.transport.routes.index', ['extra_id' => $extraId])->with('success', '✅ Circuit supprimé avec succès !');
    }

    public function stopsStore(Request $request, $routeId)
    {
        $route = ExtraRoute::whereHas('extra', fn ($q) => $q->where('school_id', session('current_school_id')))->findOrFail($routeId);

        $validated = $request->validate([
            'label' => 'required|string|max:150',
            'order' => 'nullable|integer|min:0',
            'pickup_time' => 'nullable|date_format:H:i',
        ]);

        $validated['extra_route_id'] = $route->id;
        $validated['order'] = $validated['order'] ?? ($route->stops()->max('order') + 1);
        ExtraRouteStop::create($validated);

        return redirect()->route('extras.transport.routes.index', ['extra_id' => $route->extra_id])->with('success', '✅ Arrêt ajouté avec succès !');
    }

    public function stopsDestroy($id)
    {
        $stop = ExtraRouteStop::whereHas('route.extra', fn ($q) => $q->where('school_id', session('current_school_id')))->findOrFail($id);
        $extraId = $stop->route->extra_id;
        $stop->delete();

        return redirect()->route('extras.transport.routes.index', ['extra_id' => $extraId])->with('success', '✅ Arrêt supprimé avec succès !');
    }

    // ==========================================
    // AFFECTATION DES ÉLÈVES
    // ==========================================

    public function assignmentsIndex(Request $request)
    {
        $schoolId = session('current_school_id');
        $extraId = $request->get('extra_id');

        $extras = Extra::where('school_id', $schoolId)->orderBy('name')->get();

        $subscriptions = collect();
        $routes = collect();
        if ($extraId) {
            $routes = ExtraRoute::where('extra_id', $extraId)->with('stops', 'vehicle')->orderBy('name')->get();
            $subscriptions = ExtraSubscription::where('school_id', $schoolId)
                ->where('extra_id', $extraId)
                ->where('status', 'active')
                ->with('student', 'transportAssignment.route', 'transportAssignment.stop', 'transportAssignment.vehicle')
                ->get()
                ->sortBy(fn ($s) => $s->student->last_name.$s->student->first_name);
        }

        return view('app.extras.transport.assignments', compact('extras', 'extraId', 'routes', 'subscriptions'));
    }

    public function assignmentsStore(Request $request)
    {
        $schoolId = session('current_school_id');

        $validated = $request->validate([
            'extra_subscription_id' => 'required|exists:extra_subscriptions,id',
            'extra_route_id' => 'required|exists:extra_routes,id',
            'extra_route_stop_id' => 'nullable|exists:extra_route_stops,id',
        ]);

        $subscription = ExtraSubscription::where('school_id', $schoolId)->findOrFail($validated['extra_subscription_id']);
        $route = ExtraRoute::findOrFail($validated['extra_route_id']);

        if (! empty($validated['extra_route_stop_id'])) {
            $stopBelongsToRoute = $route->stops()->where('id', $validated['extra_route_stop_id'])->exists();
            if (! $stopBelongsToRoute) {
                return back()->withErrors(['error' => "L'arrêt sélectionné n'appartient pas au circuit choisi."]);
            }
        }

        if ($route->extra_vehicle_id) {
            $vehicle = $route->vehicle;
            $alreadyAssigned = ExtraTransportAssignment::where('extra_subscription_id', $subscription->id)->exists();
            if (! $alreadyAssigned && $vehicle && ! $vehicle->hasAvailableCapacity()) {
                return back()->withErrors(['error' => "⚠️ Le véhicule « {$vehicle->plate_number} » a atteint sa capacité maximale."]);
            }
        }

        ExtraTransportAssignment::updateOrCreate(
            ['extra_subscription_id' => $subscription->id],
            [
                'extra_route_id' => $route->id,
                'extra_route_stop_id' => $validated['extra_route_stop_id'] ?? null,
                'extra_vehicle_id' => $route->extra_vehicle_id,
            ]
        );

        return back()->with('success', '✅ Élève affecté au circuit avec succès !');
    }

    public function assignmentsDestroy($id)
    {
        $assignment = ExtraTransportAssignment::whereHas('subscription', fn ($q) => $q->where('school_id', session('current_school_id')))->findOrFail($id);
        $assignment->delete();

        return back()->with('success', '✅ Affectation supprimée avec succès !');
    }

    // ==========================================
    // GÉOLOCALISATION DES VÉHICULES
    // ==========================================

    public function trackingIndex()
    {
        $schoolId = session('current_school_id');

        $vehicles = ExtraVehicle::where('school_id', $schoolId)
            ->where('status', 'active')
            ->orderBy('plate_number')
            ->get();

        return view('app.extras.transport.tracking', compact('vehicles'));
    }

    /**
     * Positions actuelles au format JSON, interrogé en boucle par la carte
     * (voir tracking.blade.php) pour se rafraîchir sans recharger la page.
     */
    public function trackingData()
    {
        $schoolId = session('current_school_id');

        $vehicles = ExtraVehicle::where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereNotNull('last_latitude')
            ->get()
            ->map(fn ($vehicle) => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'driver_name' => $vehicle->driver_name,
                'latitude' => (float) $vehicle->last_latitude,
                'longitude' => (float) $vehicle->last_longitude,
                'last_location_at' => $vehicle->last_location_at?->format('H:i:s'),
                'stale' => $vehicle->hasStaleLocation(),
            ]);

        return response()->json($vehicles);
    }

    /**
     * Lien + QR code de la page de partage de position, à imprimer ou envoyer
     * au chauffeur (génère le jeton de suivi s'il n'existe pas encore).
     */
    public function vehicleTrackingLink($id)
    {
        $vehicle = ExtraVehicle::where('school_id', session('current_school_id'))->findOrFail($id);
        $vehicle->ensureTrackingToken();

        $trackingUrl = route('vehicle-tracking.show', $vehicle->tracking_token);
        $qrSvg = QrCode::size(220)->generate($trackingUrl);

        return view('app.extras.transport.tracking-link', compact('vehicle', 'trackingUrl', 'qrSvg'));
    }

    public function vehicleRegenerateTrackingToken($id)
    {
        $vehicle = ExtraVehicle::where('school_id', session('current_school_id'))->findOrFail($id);
        $vehicle->update(['tracking_token' => Str::random(40)]);

        return redirect()->route('extras.transport.vehicles.tracking-link', $vehicle->id)
            ->with('success', '✅ Lien de suivi régénéré : l\'ancien lien ne fonctionne plus.');
    }
}
