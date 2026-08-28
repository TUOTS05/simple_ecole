<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_id')->constrained('extras')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->integer('periods_count')->nullable(); // nb de périodes facturées (si recurring)
            $table->string('start_period')->nullable(); // format Y-m
            $table->string('end_period')->nullable(); // format Y-m
            $table->integer('due_day')->default(5);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['extra_id', 'school_year_id', 'school_class_id'], 'extra_tarifs_extra_year_class_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_tarifs');
    }
};
