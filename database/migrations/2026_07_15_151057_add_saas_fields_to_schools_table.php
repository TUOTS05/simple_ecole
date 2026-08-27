<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // On ajoute uniquement les colonnes qui n'existent pas encore
            if (! Schema::hasColumn('schools', 'subscription_plan')) {
                $table->string('subscription_plan')->default('basic');
            }
            if (! Schema::hasColumn('schools', 'subscription_start_date')) {
                $table->date('subscription_start_date')->nullable()->after('subscription_plan');
            }
            if (! Schema::hasColumn('schools', 'subscription_end_date')) {
                $table->date('subscription_end_date')->nullable()->after('subscription_start_date');
            }

            // La colonne 'status' existe déjà, on ne la touche pas pour éviter l'erreur.
            // (Elle est déjà utilisée pour 'active', 'suspended', etc.)

            if (! Schema::hasColumn('schools', 'max_students')) {
                $table->integer('max_students')->default(100)->after('subscription_end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_start_date', 'subscription_end_date', 'max_students']);
        });
    }
};
