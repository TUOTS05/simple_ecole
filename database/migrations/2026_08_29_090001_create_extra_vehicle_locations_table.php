<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_vehicle_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_vehicle_id')->constrained('extra_vehicles')->onDelete('cascade');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kmh', 6, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['extra_vehicle_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_vehicle_locations');
    }
};
