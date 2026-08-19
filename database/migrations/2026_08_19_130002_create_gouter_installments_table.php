<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gouter_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gouter_subscription_id')->constrained('gouter_subscriptions')->cascadeOnDelete();
            $table->string('label'); // ex: "Échéance 1/3"
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending|partial|paid|overdue
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gouter_installments');
    }
};
