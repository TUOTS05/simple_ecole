<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_id')->constrained('extras')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0 = dimanche ... 6 = samedi
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_schedules');
    }
};
