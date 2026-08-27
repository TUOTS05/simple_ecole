<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('student_installment_id')
                ->nullable()
                ->after('enrollment_id')
                ->constrained('student_installments')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['student_installment_id']);
            $table->dropColumn('student_installment_id');
        });
    }
};
