<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Groupes de crèche : ce sont des classes (cycle = creche) qui gardent inscriptions, frais et
 * présences existants, avec les informations propres à la petite enfance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('name');
            $table->unsignedSmallInteger('age_min_months')->nullable()->after('capacity');
            $table->unsignedSmallInteger('age_max_months')->nullable()->after('age_min_months');
            $table->string('room', 100)->nullable()->after('age_max_months');
            $table->foreignId('responsible_user_id')->nullable()->after('room')->constrained('users')->nullOnDelete();
            $table->time('opening_time')->nullable()->after('responsible_user_id');
            $table->time('closing_time')->nullable()->after('opening_time');
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_user_id');
            $table->dropColumn(['code', 'age_min_months', 'age_max_months', 'room', 'opening_time', 'closing_time']);
        });
    }
};
