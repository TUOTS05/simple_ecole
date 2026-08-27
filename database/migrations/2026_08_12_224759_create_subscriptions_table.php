<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->string('plan_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('active'); // active, expired, cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
