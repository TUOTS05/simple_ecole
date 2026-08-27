<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Ajoute la colonne JSON pour les 4 documents (déjà créée par une migration précédente sur certains environnements)
            if (! Schema::hasColumn('students', 'documents')) {
                $table->json('documents')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'documents')) {
                $table->dropColumn('documents');
            }
        });
    }
};
