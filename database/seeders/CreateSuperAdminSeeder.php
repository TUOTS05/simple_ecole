<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@saas.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'school_id' => null,
        ]);

        $this->command->info('✅ Super Admin créé avec succès !');
        $this->command->info('📧 Email: admin@saas.com');
        $this->command->info('🔑 Mot de passe: password123');
    }
}
