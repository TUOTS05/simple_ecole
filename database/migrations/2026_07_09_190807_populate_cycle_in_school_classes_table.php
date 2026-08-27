<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Niveaux maternelle
        $maternelleLevels = ['TPS', 'PS', 'MS', 'GS'];

        // Niveaux primaire
        $primaireLevels = ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];

        // Mettre à jour les classes de maternelle
        DB::table('school_classes')
            ->whereIn('level', $maternelleLevels)
            ->update(['cycle' => 'maternelle']);

        // Mettre à jour les classes de primaire
        DB::table('school_classes')
            ->whereIn('level', $primaireLevels)
            ->update(['cycle' => 'primaire']);
    }

    public function down(): void
    {
        DB::table('school_classes')
            ->update(['cycle' => null]);
    }
};
