<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_subscription_id')->constrained('extra_subscriptions')->onDelete('cascade');
            $table->date('date');
            $table->string('status')->default('present'); // present, absent
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->integer('overage_minutes')->nullable();
            $table->decimal('overage_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['extra_subscription_id', 'date'], 'extra_attendances_sub_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_attendances');
    }
};
