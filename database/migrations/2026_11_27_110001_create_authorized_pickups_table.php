<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personnes autorisées (ou explicitement interdites) à récupérer un enfant de la crèche.
 * Suppression douce : une personne retirée reste traçable dans l'historique des départs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorized_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('relationship', 50);
            $table->string('photo')->nullable();
            $table->string('status', 20)->default('authorized'); // authorized | forbidden
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status']);
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorized_pickups');
    }
};
