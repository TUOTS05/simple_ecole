<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->string('period'); // Mensuel, Trimestriel
            $table->string('month')->nullable(); // Pour les compositions mensuelles
            $table->integer('quarter')->nullable(); // 1, 2, 3 pour les trimestres
            $table->decimal('score', 5, 2); // Note sur 20 ou 100
            $table->decimal('max_score', 5, 2)->default(20); // Note max (20 ou 100)
            $table->text('remarks')->nullable(); // Appréciation
            $table->foreignId('marked_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};