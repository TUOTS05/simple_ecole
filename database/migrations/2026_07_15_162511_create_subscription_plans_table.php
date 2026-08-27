<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Basique", "Premium", "Entreprise"
            $table->string('slug')->unique(); // Ex: "basic", "premium", "enterprise"
            $table->text('description')->nullable();
            $table->integer('max_students')->default(100);
            $table->integer('max_teachers')->default(10);
            $table->integer('max_classes')->default(20);
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // Fonctionnalités incluses (JSON)
            $table->integer('sort_order')->default(0); // Pour l'ordre d'affichage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
