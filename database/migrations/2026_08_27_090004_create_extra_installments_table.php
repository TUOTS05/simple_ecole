<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_subscription_id')->constrained('extra_subscriptions')->onDelete('cascade');
            $table->string('period'); // format Y-m, ou 'unique' si billing_type = one_time
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, partial, paid, overdue
            $table->timestamps();

            $table->unique(['extra_subscription_id', 'period'], 'extra_inst_sub_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_installments');
    }
};
