<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un contrat signé ne se modifie plus : un changement en cours de période passe par un avenant (nouveau
 * document rattaché au contrat d'origine), une nouvelle période par un renouvellement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('type', 20)->default('contrat')->after('contract_number'); // contrat | renouvellement | avenant
            $table->foreignId('parent_contract_id')->nullable()->after('type')->constrained('contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_contract_id');
            $table->dropColumn('type');
        });
    }
};
