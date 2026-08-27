<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('installment_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('type', 20)->default('sms');
            $table->string('category', 50)->default('late_payment');
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->string('provider_response_id')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'installment_id', 'type', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};
