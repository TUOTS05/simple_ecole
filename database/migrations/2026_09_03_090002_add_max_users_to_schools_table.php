<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Plafond d'utilisateurs (personnel, hors comptes parents) copié depuis le plan
            // d'abonnement de l'école, comme max_students. Nullable = pas encore de plan
            // assigné, donc pas de plafond appliqué.
            if (! Schema::hasColumn('schools', 'max_users')) {
                $table->integer('max_users')->nullable()->after('max_students');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('max_users');
        });
    }
};
