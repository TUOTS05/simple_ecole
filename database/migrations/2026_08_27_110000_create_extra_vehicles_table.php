<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('plate_number');
            $table->integer('capacity');
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('assistant_name')->nullable();
            $table->string('status')->default('active'); // active, maintenance, inactive
            $table->timestamps();

            $table->unique(['school_id', 'plate_number'], 'extra_vehicles_school_plate_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_vehicles');
    }
};
