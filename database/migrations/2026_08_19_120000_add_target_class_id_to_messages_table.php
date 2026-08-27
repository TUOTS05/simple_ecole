<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'target_class_id')) {
                $table->foreignId('target_class_id')
                    ->nullable()
                    ->after('target_info')
                    ->constrained('school_classes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'target_class_id')) {
                $table->dropForeign(['target_class_id']);
                $table->dropColumn('target_class_id');
            }
        });
    }
};
