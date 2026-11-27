<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier crèche d'un enfant : ce qui s'ajoute à sa fiche élève commune (inchangée) pour sa prise
 * en charge au quotidien — alimentation, sommeil, hygiène, habitudes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_care_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();

            // Informations générales
            $table->date('entry_date')->nullable();
            $table->string('care_arrangement', 30)->nullable();
            $table->foreignId('referent_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Alimentation
            $table->string('milk_type', 100)->nullable();
            $table->boolean('breastfed')->default(false);
            $table->boolean('formula_fed')->default(false);
            $table->string('usual_quantity', 50)->nullable();
            $table->string('feeding_frequency', 100)->nullable();
            $table->text('introduced_foods')->nullable();
            $table->text('forbidden_foods')->nullable();
            $table->text('allergies')->nullable();
            $table->text('food_preferences')->nullable();

            // Sommeil
            $table->string('usual_sleep_time', 50)->nullable();
            $table->string('usual_sleep_duration', 50)->nullable();
            $table->text('sleep_ritual')->nullable();
            $table->string('comforter', 150)->nullable();
            $table->boolean('pacifier')->default(false);
            $table->text('sleep_notes')->nullable();

            // Hygiène
            $table->text('changing_habits')->nullable();
            $table->text('specific_products')->nullable();
            $table->string('cream', 150)->nullable();
            $table->text('hygiene_notes')->nullable();

            // Particularités communiquées par les parents
            $table->text('particularities')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_care_profiles');
    }
};
