<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une école peut activer plusieurs structures (crèche, maternelle, primaire) : schools.structures
 * (liste JSON) remplace progressivement school_type, qui reste tenu à jour pour compatibilité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->json('structures')->nullable()->after('school_type');
        });

        $map = [
            'maternelle' => '["maternelle"]',
            'primaire' => '["primaire"]',
            'creche' => '["creche"]',
        ];
        foreach ($map as $type => $json) {
            DB::table('schools')->where('school_type', $type)->update(['structures' => $json]);
        }
        // 'both' et toute valeur inattendue : maternelle + primaire (comportement historique)
        DB::table('schools')->whereNull('structures')->update(['structures' => '["maternelle","primaire"]']);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('structures');
        });
    }
};
