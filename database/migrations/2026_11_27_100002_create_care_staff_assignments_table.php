<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Affectation du personnel de crèche (auxiliaires, accueil, responsable) aux groupes. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_referent')->default(false);
            $table->timestamps();

            $table->unique(['school_class_id', 'user_id'], 'care_staff_assignments_class_user_unique');
            $table->index(['school_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_staff_assignments');
    }
};
