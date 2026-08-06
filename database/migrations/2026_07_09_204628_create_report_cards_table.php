<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('period'); // Mensuel ou Trimestriel
            $table->string('month')->nullable();
            $table->integer('quarter')->nullable();
            $table->decimal('average', 5, 2); // Moyenne générale
            $table->integer('rank')->nullable(); // Rang dans la classe
            $table->integer('total_students')->nullable(); // Total élèves dans la classe
            $table->text('teacher_comment')->nullable(); // Appréciation de l'enseignant
            $table->text('director_comment')->nullable(); // Appréciation du directeur
            $table->boolean('director_signed')->default(false);
            $table->boolean('parent_signed')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['student_id', 'school_year_id', 'period', 'month', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};