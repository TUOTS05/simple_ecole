<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            
            $table->enum('type', ['registration', 'installment'])->default('installment');
            $table->string('description'); // Ex: "Frais d'inscription" ou "1ère échéance"
            
            $table->decimal('amount', 10, 2); // Montant total dû
            $table->decimal('paid_amount', 10, 2)->default(0); // Montant déjà payé
            
            $table->date('due_date'); // Date limite de paiement
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            
            $table->timestamps();
            
            // Index pour accélérer les requêtes
            $table->index(['enrollment_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_installments');
    }
};