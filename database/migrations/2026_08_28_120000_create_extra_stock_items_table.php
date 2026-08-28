<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('unit', 30)->default('unité');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('alert_threshold')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_stock_items');
    }
};
