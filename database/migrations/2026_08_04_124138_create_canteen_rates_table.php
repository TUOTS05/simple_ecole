<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->decimal('monthly_rate', 12, 2); // Ex: 15000.00
            $table->integer('months_count')->default(10); // Nombre de mois facturés
            $table->string('start_month'); // Ex: '2026-09'
            $table->string('end_month');   // Ex: '2027-06'
            $table->text('description')->nullable();
            $table->timestamps();

            // Un tarif par classe par année
            $table->unique(['school_class_id', 'school_year_id'], 'canteen_rates_class_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_rates');
    }
};
