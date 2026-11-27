<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal quotidien de l'enfant : une table générique pour biberons, repas, goûters, sommeil,
 * changes, hygiène, activités et observations (les détails propres à chaque type sont dans
 * « data »). Suppression douce + deleted_by : un événement supprimé reste traçable.
 * uuid / device_id / sync_status préparent la saisie hors ligne (synchronisation future).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('event_type', 20);
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->json('data')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_id', 64)->nullable();
            $table->string('sync_status', 20)->default('synced');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'event_date']);
            $table->index(['school_id', 'event_date']);
            $table->index(['school_class_id', 'event_date', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_events');
    }
};
