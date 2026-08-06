<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            
            // Parent (utilisateur avec role='parent')
            $table->foreignId('parent_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Élève
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->onDelete('cascade');
            
            // École (pour gérer multi-écoles)
            $table->foreignId('school_id')
                  ->constrained('schools')
                  ->onDelete('cascade');
            
            // Relation unique : un parent ne peut être lié qu'une fois au même élève dans la même école
            $table->unique(['parent_id', 'student_id', 'school_id']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
    }
};