<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creche_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->onDelete('set null');
            $table->date('date');
            $table->string('status')->default('present'); // present, absent
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->string('dropped_off_by')->nullable();
            $table->string('picked_up_by')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['student_id', 'date'], 'creche_attendances_student_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creche_attendances');
    }
};
