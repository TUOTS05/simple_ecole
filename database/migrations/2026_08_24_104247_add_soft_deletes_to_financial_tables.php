<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * students, payments, enrollments, grades, report_cards et contracts
     * sont liées entre elles (et à d'autres tables) via cascadeOnDelete()
     * au niveau SQL, mais n'avaient pas de soft delete Eloquent, ce qui
     * exposait ces données financières/académiques à une perte
     * irréversible en cas de suppression accidentelle.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
