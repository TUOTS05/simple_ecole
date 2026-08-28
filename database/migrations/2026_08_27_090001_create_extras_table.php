<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('extra_category_id')->constrained('extra_categories')->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->string('target_audience')->nullable(); // ex: "Tous", "Maternelle"
            $table->string('billing_type')->default('recurring'); // recurring, one_time
            $table->integer('capacity')->nullable(); // null = illimité
            $table->foreignId('responsible_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('conditions')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'extras_school_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extras');
    }
};
