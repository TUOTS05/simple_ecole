<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('contract_number')->unique(); // Numéro de contrat (ex: CTR-2026-001)
            $table->string('plan_name'); // Essentiel, Prémium, etc.
            $table->decimal('amount', 10, 2); // Montant en FCFA
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('max_students');
            $table->integer('max_teachers');
            $table->string('status')->default('pending'); // pending, active, expired, cancelled
            $table->string('pdf_path')->nullable(); // Chemin vers le PDF généré
            $table->timestamp('signed_at')->nullable(); // Date de signature
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
