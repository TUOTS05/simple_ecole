<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fonctions administratives définies par chaque école, avec les modules qu'elles ouvrent
        // (clés de config/school_permissions.php). Les comptes rattachés ont le rôle « staff ».
        Schema::create('staff_functions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('staff_function_id')->nullable()->after('role')
                ->constrained('staff_functions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_function_id');
        });

        Schema::dropIfExists('staff_functions');
    }
};
