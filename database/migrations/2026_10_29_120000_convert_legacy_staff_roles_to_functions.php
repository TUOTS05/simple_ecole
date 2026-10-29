<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Anciens rôles fixes à accès restreint => fonction administrative équivalente
     * (même périmètre, voir config/school_permissions.php).
     */
    private const ROLES = [
        'accountant' => [
            'name' => 'Comptable',
            'description' => 'Inscriptions, paiements et facturation des extras',
            'permissions' => ['enrollments', 'payments', 'extras_billing'],
        ],
        'canteen_manager' => [
            'name' => 'Responsable cantine',
            'description' => 'Présences/consommations et menus de la cantine',
            'permissions' => ['extras_canteen'],
        ],
        'transport_manager' => [
            'name' => 'Responsable transport',
            'description' => 'Véhicules, circuits, affectations et suivi GPS',
            'permissions' => ['transport'],
        ],
    ];

    /**
     * Le personnel comptable, cantine et transport devient du personnel administratif comme les
     * autres : chaque école concernée reçoit la fonction équivalente (créée, ou réutilisée si
     * l'école en a déjà une de ce nom) et ces comptes passent au rôle « staff ».
     */
    public function up(): void
    {
        foreach (self::ROLES as $role => $function) {
            $schoolIds = DB::table('users')->where('role', $role)->whereNotNull('school_id')
                ->distinct()->pluck('school_id');

            foreach ($schoolIds as $schoolId) {
                $functionId = DB::table('staff_functions')
                    ->where('school_id', $schoolId)->where('name', $function['name'])->value('id')
                    ?? DB::table('staff_functions')->insertGetId([
                        'school_id' => $schoolId,
                        'name' => $function['name'],
                        'description' => $function['description'],
                        'permissions' => json_encode($function['permissions']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('users')->where('role', $role)->where('school_id', $schoolId)
                    ->update(['role' => 'staff', 'staff_function_id' => $functionId]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::ROLES as $role => $function) {
            $functionIds = DB::table('staff_functions')->where('name', $function['name'])->pluck('id');

            DB::table('users')->where('role', 'staff')->whereIn('staff_function_id', $functionIds)
                ->update(['role' => $role, 'staff_function_id' => null]);
        }
    }
};
