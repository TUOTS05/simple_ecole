<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La capacité d'une classe est facultative dans le formulaire (vide = pas de limite), mais la colonne
 * était obligatoire : créer une classe sans capacité provoquait une erreur SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->integer('capacity')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->integer('capacity')->default(25)->nullable(false)->change();
        });
    }
};
