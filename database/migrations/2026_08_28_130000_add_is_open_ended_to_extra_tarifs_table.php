<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extra_tarifs', function (Blueprint $table) {
            // Facturation mensuelle continue (cantine, garderie, transport...) : une
            // nouvelle échéance est générée chaque mois tant que l'abonnement reste actif,
            // au lieu du nombre de périodes fixé une fois pour toutes à la souscription.
            $table->boolean('is_open_ended')->default(false)->after('periods_count');
        });
    }

    public function down(): void
    {
        Schema::table('extra_tarifs', function (Blueprint $table) {
            $table->dropColumn('is_open_ended');
        });
    }
};
