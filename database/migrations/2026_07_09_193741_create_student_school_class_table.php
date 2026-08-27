<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_school_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->timestamps();

            // Un élève ne peut être dans la même classe qu'une seule fois
            $table->unique(['student_id', 'school_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_school_class');
    }
};
