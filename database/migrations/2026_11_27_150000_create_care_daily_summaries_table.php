<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carnet de vie du jour : l'humeur et le message de l'auxiliaire, publiés aux parents en fin de
 * journée. Le reste du carnet (présence, repas, sommeil...) est calculé à partir du journal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('summary_date');
            $table->string('mood', 20)->nullable();
            $table->text('message')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'summary_date']);
            $table->index(['school_id', 'summary_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_daily_summaries');
    }
};
