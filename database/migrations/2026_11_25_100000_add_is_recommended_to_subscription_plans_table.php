<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le badge "⭐ POPULAIRE" de la page publique était attribué au 2e plan affiché
     * ($index === 1), donc à "Essentiel" uniquement parce que le plan "Essai" était
     * en 1re position. Sans le plan Essai, il glissait sur "Croissance". On le rend
     * explicite et pilotable par le super admin via ce champ.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_recommended')->default(false)->after('is_active');
        });

        // Conserve l'affichage actuel : "Essentiel" reste le plan mis en avant.
        DB::table('subscription_plans')->where('name', 'Essentiel')->update(['is_recommended' => true]);
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('is_recommended');
        });
    }
};
