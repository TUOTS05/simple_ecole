<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->enum('end_of_year_decision', ['en_attente', 'admis', 'redouble', 'saut_classe'])
                  ->default('en_attente')
                  ->after('director_comment');
            
            $table->foreignId('next_school_class_id')
                  ->nullable()
                  ->constrained('school_classes')
                  ->nullOnDelete()
                  ->after('end_of_year_decision');
        });
    }

    public function down(): void {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropForeign(['next_school_class_id']);
            $table->dropColumn(['end_of_year_decision', 'next_school_class_id']);
        });
    }
};