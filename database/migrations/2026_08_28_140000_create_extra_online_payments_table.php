<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('extra_subscription_id')->constrained('extra_subscriptions')->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 12, 2);
            // pending, completed, failed
            $table->string('status', 20)->default('pending');
            $table->string('payment_token')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('extra_payment_id')->nullable()->constrained('extra_payments')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->text('gateway_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_online_payments');
    }
};
