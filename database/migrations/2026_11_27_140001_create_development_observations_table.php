<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi du développement de l'enfant par domaine (motricité, langage, ...) : niveaux
 * « observé », « en cours », « à stimuler ». Ce ne sont pas des notes scolaires.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('observed_on');
            $table->string('domain', 50);
            $table->string('level', 20); // observed | in_progress | to_stimulate
            $table->text('notes')->nullable();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'observed_on']);
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_observations');
    }
};
