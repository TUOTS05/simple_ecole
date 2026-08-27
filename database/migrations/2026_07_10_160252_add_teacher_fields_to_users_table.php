<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajout du rôle si non existant (par défaut 'teacher' pour ce module)
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('teacher')->after('email');
            }
            // Ajout du téléphone
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('role');
            }
            // Ajout du genre (M/F)
            if (! Schema::hasColumn('users', 'gender')) {
                $table->char('gender', 1)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'gender']);
        });
    }
};
