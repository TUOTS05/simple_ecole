<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personnalisation des matières : regroupement par domaine (ex. « Français »,
     * « Mathématiques ») sur le bulletin, et ordre d'affichage choisi par l'école.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('domain', 100)->nullable()->after('name');
            $table->unsignedInteger('sort_order')->default(0)->after('max_score');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['domain', 'sort_order']);
        });
    }
};
