<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->string('level'); // ex: "TPS", "CP1", "CM2"
            $table->enum('fee_type', ['registration', 'tuition']); // inscription ou scolarité
            $table->decimal('amount', 12, 2); // montant en FCFA
            $table->string('description')->nullable();
            $table->timestamps();

            // Un seul montant par école, année, niveau et type
            $table->unique(['school_id', 'school_year_id', 'level', 'fee_type'], 'fee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
