<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit : rattache chaque action à une école, à un utilisateur et à l'élément concerné
 * (ex. un enfant), pour pouvoir retrouver « qui a fait quoi sur cet enfant, dans cette école ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('school_id')->constrained('users')->nullOnDelete();
            $table->string('subject_type', 100)->nullable()->after('description');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->json('meta')->nullable()->after('subject_id');

            $table->index(['school_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Clés étrangères d'abord : MySQL refuse de supprimer un index qu'une clé utilise.
            $table->dropForeign(['school_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['school_id', 'created_at']);
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['school_id', 'user_id', 'subject_type', 'subject_id', 'meta']);
        });
    }
};
