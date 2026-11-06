<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le matricule (ANNÉE-XXX-NNN) est numéroté par école à partir des 3 premières lettres du slug :
 * deux écoles dont le slug commence pareil (« college-a », « college-b ») produisaient le même
 * matricule et l'unicité globale faisait échouer l'inscription. Il n'est unique qu'au sein d'une école,
 * et toutes les recherches par matricule sont déjà filtrées par école.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['matricule']);
            $table->unique(['school_id', 'matricule']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'matricule']);
            $table->unique('matricule');
        });
    }
};
