<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->onDelete('set null');
            $table->enum('status', ['reserved', 'enrolled', 'withdrawn'])->default('reserved');
            $table->date('enrollment_date');

            // Frais d'inscription
            $table->boolean('registration_fee_paid')->default(false);

            // Frais de scolarité
            $table->decimal('tuition_fee_total', 12, 2)->default(0);
            $table->decimal('tuition_fee_paid', 12, 2)->default(0);
            $table->decimal('tuition_fee_remaining', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            // Un élève ne peut être inscrit qu'une fois par année scolaire
            $table->unique(['student_id', 'school_year_id'], 'enrollment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
