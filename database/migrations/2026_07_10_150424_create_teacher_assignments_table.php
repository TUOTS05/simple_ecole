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
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // L'enseignant
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            
            // Distinguer le titulaire de l'adjoint (si 2 enseignants par classe)
            $table->boolean('is_main_teacher')->default(true); 
            
            $table->timestamps();

            // Un enseignant ne peut pas être deux fois dans la même classe pour la même année
            $table->unique(['school_class_id', 'user_id', 'school_year_id'], 'teacher_assignments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
