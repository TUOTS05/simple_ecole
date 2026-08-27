<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // On ajoute receiver_id UNIQUEMENT s'il n'existe pas déjà
            if (! Schema::hasColumn('messages', 'receiver_id')) {
                $table->foreignId('receiver_id')
                    ->nullable()
                    ->after('sender_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'receiver_id')) {
                $table->dropForeign(['receiver_id']);
                $table->dropColumn('receiver_id');
            }
        });
    }
};
