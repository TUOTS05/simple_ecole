<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gouter_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_modality')->default('unique'); // unique|mensuel|trimestriel|semestriel
            $table->unsignedInteger('number_of_installments')->default(1);
            $table->decimal('installment_amount', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['school_class_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gouter_rates');
    }
};
