<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('recipient_phone', 20);
            $table->string('recipient_name')->nullable();
            $table->text('message');
            $table->string('gateway', 50)->default('orange_sms'); // orange_sms, log, etc.
            $table->enum('status', ['pending', 'sent', 'failed', 'queued'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('external_id')->nullable(); // ID retourné par le fournisseur
            $table->decimal('cost', 10, 2)->default(0); // Coût en devise locale
            $table->string('trigger_type', 50)->default('absence'); // absence, payment, custom
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};