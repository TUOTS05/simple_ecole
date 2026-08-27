<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSchoolSeeder extends Seeder
{
    public function run()
    {
        // 1. Créer l'école de démo
        $school = School::updateOrCreate(
            ['email' => 'demo-ecole@schoolmanager.com'],
            [
                'name' => 'École Les Mirabelles (Démo)',
                'slug' => 'ecole-les-mirabelles-demo-'.time(),
                'phone' => '+225 07 00 00 00 00',
                'address' => 'Cocody, Abidjan, Côte d\'Ivoire',
                'school_type' => 'both',
                'status' => 'active',
                'subscription_plan' => 'premium',
                'subscription_start_date' => now(),
                'subscription_end_date' => now()->addYear(),
                'trial_ends_at' => now()->addYear(),
                'is_active' => true,
                'max_students' => 500,
            ]
        );

        // 2. Créer l'utilisateur Admin de démo
        $admin = User::updateOrCreate(
            ['email' => 'demo@schoolmanager.com'],
            [
                'school_id' => $school->id,
                'first_name' => 'Administrateur',
                'last_name' => 'Démo',
                'password' => Hash::make('demo1234'),
                'role' => 'school_admin',
            ]
        );

        // 3. Année scolaire
        $schoolYear = SchoolYear::updateOrCreate(
            ['school_id' => $school->id, 'name' => '2025-2026'],
            [
                'start_date' => '2025-09-01',
                'end_date' => '2026-07-31',
                'is_active' => true,
            ]
        );

        // 4. Classes de démo
        $classCP1 = SchoolClass::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'CP1 A'],
            [
                'level' => 'CP',
                'registration_fee' => 50000,
                'total_tuition' => 120000,
                'payment_modality' => 'trimestriel',
                'number_of_installments' => 3,
                'installment_amount' => 40000,
            ]
        );

        $classCM1 = SchoolClass::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'CM1 A'],
            [
                'level' => 'CM1',
                'registration_fee' => 50000,
                'total_tuition' => 150000,
                'payment_modality' => 'trimestriel',
                'number_of_installments' => 3,
                'installment_amount' => 50000,
            ]
        );

        // 5. Élèves de démo
        $studentsData = [
            ['mat' => 'DEMO-0001', 'last' => 'COULIBALY', 'first' => 'Karnan', 'gender' => 'M', 'birth' => '2019-10-15', 'class' => $classCP1],
            ['mat' => 'DEMO-0021', 'last' => 'KAMAGATÉ', 'first' => 'Zoumana', 'gender' => 'M', 'birth' => '2017-06-13', 'class' => $classCP1],
            ['mat' => 'DEMO-0011', 'last' => 'SANOGO', 'first' => 'Massadje', 'gender' => 'F', 'birth' => '2019-06-14', 'class' => $classCP1],
            ['mat' => 'DEMO-0018', 'last' => 'SORO', 'first' => 'Fatou', 'gender' => 'F', 'birth' => '2018-05-15', 'class' => $classCP1],
            ['mat' => 'DEMO-0009', 'last' => 'GBON', 'first' => 'Chiontchan', 'gender' => 'M', 'birth' => '2015-03-10', 'class' => $classCM1],
            ['mat' => 'DEMO-0024', 'last' => 'SORO', 'first' => 'Magnigui', 'gender' => 'F', 'birth' => '2015-08-22', 'class' => $classCM1],
            ['mat' => 'DEMO-0016', 'last' => 'SORO', 'first' => 'Sita', 'gender' => 'F', 'birth' => '2015-11-05', 'class' => $classCM1],
            ['mat' => 'DEMO-0014', 'last' => 'YÉO', 'first' => 'Salimata', 'gender' => 'F', 'birth' => '2015-09-12', 'class' => $classCM1],
        ];

        foreach ($studentsData as $data) {
            $student = Student::updateOrCreate(
                ['matricule' => $data['mat']],
                [
                    'school_id' => $school->id,
                    'first_name' => $data['first'],
                    'last_name' => $data['last'],
                    'gender' => $data['gender'],
                    'birth_date' => $data['birth'],
                    'status' => 'active',
                    'guardian_phone' => '0707070707',
                    'guardian_name' => 'Parent Démo',
                ]
            );

            $enrollment = Enrollment::updateOrCreate(
                ['school_id' => $school->id, 'student_id' => $student->id, 'school_year_id' => $schoolYear->id],
                [
                    'school_class_id' => $data['class']->id,
                    'enrollment_date' => '2025-09-01',
                    'status' => 'enrolled',
                ]
            );

            // ✅ CORRECTION ICI : 'type' => 'installment' (et non 'tuition')
            StudentInstallment::updateOrCreate(
                ['enrollment_id' => $enrollment->id, 'description' => 'Frais de scolarité 2025-2026'],
                [
                    'school_id' => $school->id,
                    'type' => 'installment', // <-- CORRECTION APPLIQUÉE
                    'amount' => 300000,
                    'paid_amount' => 150000,
                    'due_date' => '2025-12-31',
                    'status' => 'partial',
                ]
            );
        }

        $this->command->info('✅ Compte et données de démo créés avec succès !');
        $this->command->info('📧 Email: demo@schoolmanager.com');
        $this->command->info('🔑 Mot de passe: demo1234');
    }
}
