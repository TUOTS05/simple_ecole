<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Incidents de la crèche (chute, blessure, comportement inhabituel, malaise signalé...).
 * Déclaré par l'auxiliaire, validé par la responsable ; une fois validé, l'incident n'est plus
 * modifiable : on ne peut qu'y ajouter des suivis (follow_ups, ajout seul). Jamais supprimé :
 * une déclaration erronée est annulée (statut « cancelled ») avec son motif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->date('occurred_on');
            $table->time('occurred_at');
            $table->string('type', 30);
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->string('person_informed', 150)->nullable();
            $table->dateTime('informed_at')->nullable();
            $table->string('status', 20)->default('declared'); // declared | validated | cancelled
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable();
            $table->text('validation_comment')->nullable();
            $table->json('follow_ups')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['student_id', 'occurred_on']);
            $table->index(['school_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_incidents');
    }
};
