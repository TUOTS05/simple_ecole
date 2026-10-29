<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Mot de passe commun à tous les utilisateurs générés (haché une seule fois).
     */
    protected static ?string $password;

    /**
     * Valeurs par défaut des utilisateurs générés.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * role/school_id ne sont pas mass-assignables sur User (voir $fillable) : on les
     * affecte directement sur le modèle après make(), avant persistance.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->role ??= 'parent';
        });
    }

    /**
     * Variante : adresse e-mail non vérifiée.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRole(string $role): static
    {
        return $this->afterMaking(function (User $user) use ($role) {
            $user->role = $role;
        });
    }

    public function forSchool(int $schoolId): static
    {
        return $this->afterMaking(function (User $user) use ($schoolId) {
            $user->school_id = $schoolId;
        });
    }
}
