<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ÉCOLES (La fondation)
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('trial');
            $table->timestamps();
        });

        // 3. ÉLÈVES
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->string('gender');
            $table->string('photo_url')->nullable();
            $table->json('medical_info')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 4. CLASSES
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('level');
            $table->integer('capacity')->default(25);
            $table->timestamps();
        });

        // 5. PRÉSENCES
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('date');
            $table->string('status');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('notified_parent')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'date']);
        });

        // 6. TABLE PIVOT : CLASSE <-> ÉLÈVE
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year');
            $table->timestamps();

            $table->unique(['school_class_id', 'student_id', 'academic_year']);
        });

        // 7. TABLE PIVOT : PARENT <-> ÉLÈVE
        Schema::create('student_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship')->default('guardian');
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();
        });

        // 8. AJOUTER LA CONTRAINTE USERS -> SCHOOLS (à la fin, quand schools existe)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_parent');
        Schema::dropIfExists('class_student');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('students');
        Schema::dropIfExists('schools');
    }
};
