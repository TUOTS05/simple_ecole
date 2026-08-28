<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_id')->constrained('extras')->onDelete('cascade');
            $table->foreignId('extra_vehicle_id')->nullable()->constrained('extra_vehicles')->onDelete('set null');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_routes');
    }
};
