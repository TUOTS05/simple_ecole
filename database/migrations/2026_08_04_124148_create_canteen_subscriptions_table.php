<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('canteen_rate_id')->constrained('canteen_rates')->onDelete('cascade');
            $table->decimal('total_amount', 12, 2); // monthly_rate * months_count
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2); // total - paid
            $table->string('status')->default('active'); // active, suspended, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            // Un élève ne peut être inscrit qu'une fois par année à la cantine
            $table->unique(['student_id', 'school_year_id'], 'canteen_subs_student_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_subscriptions');
    }
};