<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentification de l'API parent (jetons Sanctum).
 *
 * Réservée aux comptes parents : l'API publique n'expose que l'espace
 * parent, pas l'administration de l'école.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:100',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            // Message volontairement identique dans les deux cas : ne pas
            // révéler si l'adresse existe.
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (! $user->isParent()) {
            return response()->json([
                'message' => 'Cette API est réservée aux comptes parents.',
            ], 403);
        }

        // Un jeton par appareil : reconnecter le même appareil remplace
        // l'ancien jeton au lieu d'en empiler un nouveau.
        $user->tokens()->where('name', $validated['device_name'])->delete();

        return response()->json([
            'token' => $user->createToken($validated['device_name'])->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'school' => $user->school ? [
                'id' => $user->school->id,
                'name' => $user->school->name,
            ] : null,
        ];
    }
}
