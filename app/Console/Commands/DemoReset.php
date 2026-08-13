<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Attendance;
use App\Models\ReportCard;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\Message;
use App\Models\Enrollment;
use App\Models\StudentInstallment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DemoReset extends Command
{
    protected $signature = 'demo:reset';
    protected $description = 'Réinitialise l\'école de démonstration en relançant le DemoSchoolSeeder';

    public function handle()
    {
        $this->info('🔄 Début de la réinitialisation de la démo...');

        // 1. Cibler l'école de démo exacte (selon votre Seeder)
        $demoSchool = School::where('email', 'demo-ecole@schoolmanager.com')->first();

        if ($demoSchool) {
            $this->info("🗑️ Suppression des anciennes données de l'école de démo (ID: {$demoSchool->id})...");
            
            DB::transaction(function () use ($demoSchool) {
                // Suppression dans l'ordre inverse des dépendances pour éviter les erreurs de clés étrangères
                
                // 1. Données financières et messages
                StudentInstallment::where('school_id', $demoSchool->id)->delete();
                Payment::where('school_id', $demoSchool->id)->delete();
                Message::where('school_id', $demoSchool->id)->delete();
                
                // 2. Données liées aux élèves
                $studentIds = Student::where('school_id', $demoSchool->id)->pluck('id');
                if ($studentIds->isNotEmpty()) {
                    Attendance::whereIn('student_id', $studentIds)->delete();
                    Grade::whereIn('student_id', $studentIds)->delete();
                    ReportCard::whereIn('student_id', $studentIds)->delete();
                }

                // 3. Inscriptions et élèves
                Enrollment::where('school_id', $demoSchool->id)->delete();
                Student::where('school_id', $demoSchool->id)->delete();
                
                // 4. Classes et utilisateurs
                SchoolClass::where('school_id', $demoSchool->id)->delete();
                User::where('school_id', $demoSchool->id)->delete();
                
                // 5. Enfin, supprimer l'école elle-même (le seeder la recréera proprement)
                $demoSchool->delete();
            });
            
            $this->info('✅ Anciennes données supprimées avec succès.');
        } else {
            $this->info('ℹ️ Aucune école de démo trouvée, création d\'une nouvelle...');
        }

        // 2. Relancer le Seeder de démonstration
        $this->info("⚙️ Exécution du seeder : DemoSchoolSeeder...");
        
        $exitCode = Artisan::call('db:seed', [
            '--class' => 'DemoSchoolSeeder',
            '--force' => true,
        ]);

        if ($exitCode === 0) {
            $this->info('🎉 Réinitialisation de la démo terminée avec succès !');
            $this->info('🔑 Vous pouvez vous connecter avec : demo@schoolmanager.com / demo1234');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Une erreur est survenue lors de l\'exécution du seeder.');
            return Command::FAILURE;
        }
    }
}