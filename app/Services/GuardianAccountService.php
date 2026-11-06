<?php

namespace App\Services;

use App\Mail\ChildLinkedMail;
use App\Mail\ParentWelcomeMail;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Compte parent du tuteur saisi à l'admission ou à la modification d'un élève : création (avec un
 * mot de passe propre à chaque parent) ou rattachement d'un compte existant, puis e-mail au tuteur.
 */
class GuardianAccountService
{
    /**
     * Message d'erreur si l'adresse ne peut pas servir au tuteur (compte qui n'est pas un parent), sinon null.
     * Un parent déjà inscrit, même dans une autre école, est simplement rattaché à l'élève.
     */
    public function emailProblem(?string $email): ?string
    {
        $user = $email ? User::where('email', $email)->first() : null;

        if ($user && $user->role !== 'parent') {
            return "Cette adresse est déjà celle d'un compte « {$user->roleLabel()} ». Saisissez l'adresse personnelle du tuteur.";
        }

        return null;
    }

    /**
     * Crée ou retrouve le compte parent et le rattache à l'élève.
     *
     * @return array{user: User, password: ?string} password n'est renseigné que pour un compte créé
     */
    public function link(Student $student, array $guardian, int $schoolId): array
    {
        $user = User::where('email', $guardian['guardian_email'])->first();
        $password = null;

        if (! $user) {
            $password = Str::password(10, symbols: false);
            $user = new User([
                'first_name' => $guardian['guardian_first_name'],
                'last_name' => $guardian['guardian_last_name'],
                'email' => $guardian['guardian_email'],
                'phone' => $guardian['guardian_phone'] ?? null,
                'password' => Hash::make($password),
            ]);
            // role et school_id ne sont pas mass-assignables (protection contre l'élévation de privilèges).
            $user->role = 'parent';
            $user->school_id = $schoolId;
            $user->save();
        }

        DB::table('parent_student')->updateOrInsert(
            ['parent_id' => $user->id, 'student_id' => $student->id],
            ['school_id' => $schoolId, 'created_at' => now(), 'updated_at' => now()]
        );

        return ['user' => $user, 'password' => $password];
    }

    /**
     * Prévient le tuteur : identifiants pour un nouveau compte, simple information sinon.
     * Un échec d'envoi est journalisé sans bloquer l'admission.
     */
    public function notify(array $link, Student $student, ?School $school): void
    {
        $user = $link['user'];
        $parentName = trim($user->first_name.' '.$user->last_name);
        $studentName = trim($student->first_name.' '.$student->last_name);
        $schoolName = $school->name ?? 'votre école';

        try {
            Mail::to($user->email)->send($link['password']
                ? new ParentWelcomeMail($parentName, $studentName, $schoolName, $user->email, $link['password'])
                : new ChildLinkedMail($parentName, $studentName, $schoolName));
        } catch (\Throwable $e) {
            Log::error("Échec de l'envoi de l'e-mail au tuteur {$user->email} : ".$e->getMessage());
        }
    }
}
