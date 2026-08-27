<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Le modèle School les référence déjà (fillable/casts, isTrialActive()) mais
            // aucune migration ne les avait jamais créées, ce qui fait planter toute
            // insertion/mise à jour qui les renseigne (ex: demande de compte, approbation).
            if (! Schema::hasColumn('schools', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            if (! Schema::hasColumn('schools', 'trial_ends_at')) {
                $table->date('trial_ends_at')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'trial_ends_at']);
        });
    }
};
