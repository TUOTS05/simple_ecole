<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('extra_stock_item_id')->constrained('extra_stock_items')->onDelete('cascade');
            // in, out, sale, return
            $table->string('type', 20);
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->string('reason', 255)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_stock_movements');
    }
};
