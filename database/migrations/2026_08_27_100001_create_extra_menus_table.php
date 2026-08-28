<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('extra_id')->constrained('extras')->onDelete('cascade');
            $table->date('date');
            $table->string('entree')->nullable();
            $table->string('plat')->nullable();
            $table->string('dessert')->nullable();
            $table->string('gouter')->nullable();
            $table->timestamps();

            $table->unique(['extra_id', 'date'], 'extra_menus_extra_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_menus');
    }
};
