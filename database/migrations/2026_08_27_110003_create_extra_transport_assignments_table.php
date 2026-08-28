<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_transport_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_subscription_id')->unique()->constrained('extra_subscriptions')->onDelete('cascade');
            $table->foreignId('extra_route_id')->constrained('extra_routes')->onDelete('cascade');
            $table->foreignId('extra_route_stop_id')->nullable()->constrained('extra_route_stops')->onDelete('set null');
            $table->foreignId('extra_vehicle_id')->nullable()->constrained('extra_vehicles')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_transport_assignments');
    }
};
