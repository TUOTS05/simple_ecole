<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Hash;

class SaasDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════
        // ÉCOLE 1 : Les Mirabelles
        // ═══════════════════════════════════════════
        $school1 = School::create([
            'name' => 'École Les Mirabelles',
            'slug' => 'mirabelles',
            'status' => 'active',
        ]);

        $admin1 = User::create([
            'school_id' => $school1->id,
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'admin@mirabelles.com',
            'password' => Hash::make('password'),
            'role' => 'school_admin',
        ]);

        // Classes pour Les Mirabelles
        $class1 = SchoolClass::create([
            'school_id' => $school1->id,
            'name' => 'Grande Section A',
            'level' => 'GS',
            'capacity' => 25,
        ]);

        $class2 = SchoolClass::create([
            'school_id' => $school1->id,
            'name' => 'CP B',
            'level' => 'CP',
            'capacity' => 20,
        ]);

        // Élèves pour Les Mirabelles
        Student::create([
            'school_id' => $school1->id,
            'first_name' => 'Léo',
            'last_name' => 'Martin',
            'birth_date' => '2019-05-12',
            'gender' => 'M',
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $school1->id,
            'first_name' => 'Emma',
            'last_name' => 'Bernard',
            'birth_date' => '2018-08-22',
            'gender' => 'F',
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $school1->id,
            'first_name' => 'Lucas',
            'last_name' => 'Dubois',
            'birth_date' => '2019-03-15',
            'gender' => 'M',
            'status' => 'active',
        ]);

        // ═══════════════════════════════════════════
        // ÉCOLE 2 : Cocody Kids
        // ═══════════════════════════════════════════
        $school2 = School::create([
            'name' => 'Cocody Kids',
            'slug' => 'cocody',
            'status' => 'active',
        ]);

        $admin2 = User::create([
            'school_id' => $school2->id,
            'first_name' => 'Jean',
            'last_name' => 'Kouassi',
            'email' => 'admin@cocody.com',
            'password' => Hash::make('password'),
            'role' => 'school_admin',
        ]);

        // Classes pour Cocody Kids
        $class3 = SchoolClass::create([
            'school_id' => $school2->id,
            'name' => 'Petite Section',
            'level' => 'PS',
            'capacity' => 20,
        ]);

        // Élèves pour Cocody Kids
        Student::create([
            'school_id' => $school2->id,
            'first_name' => 'Awa',
            'last_name' => 'Diallo',
            'birth_date' => '2020-02-14',
            'gender' => 'F',
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $school2->id,
            'first_name' => 'Koffi',
            'last_name' => 'Yao',
            'birth_date' => '2019-11-08',
            'gender' => 'M',
            'status' => 'active',
        ]);

        $this->command->info('✅ Données de test créées avec succès !');
        $this->command->info('');
        $this->command->info('🏫 École 1: Les Mirabelles');
        $this->command->info('   👤 Admin: admin@mirabelles.com / password');
        $this->command->info('   👨‍🎓 3 élèves');
        $this->command->info('');
        $this->command->info('🏫 École 2: Cocody Kids');
        $this->command->info('   👤 Admin: admin@cocody.com / password');
        $this->command->info('   👨‍🎓 2 élèves');
    }
}