<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canteen_subscription_id')->constrained('canteen_subscriptions')->onDelete('cascade');
            $table->string('month'); // Format: '2026-09'
            $table->decimal('amount', 12, 2); // monthly_rate
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date'); // Premier jour du mois
            $table->string('status')->default('pending'); // pending, paid, partial, overdue
            $table->timestamps();

            // Une échéance par mois par abonnement
            $table->unique(['canteen_subscription_id', 'month'], 'canteen_inst_sub_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_installments');
    }
};