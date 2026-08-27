<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->string('name'); // Nom de la matière
            $table->string('cycle'); // maternelle ou primaire
            $table->string('level'); // PS, MS, GS, CP, CP1, CP2, CE1, CE2, CM1, CM2
            $table->string('coefficient')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'school_year_id', 'cycle', 'level', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
