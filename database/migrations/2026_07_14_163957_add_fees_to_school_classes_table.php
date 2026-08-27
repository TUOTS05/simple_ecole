<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->decimal('total_tuition', 10, 2)->nullable()->after('level')->comment('Montant total de la scolarité');
            $table->decimal('registration_fee', 10, 2)->nullable()->after('total_tuition')->comment('Montant à payer à l\'inscription');
            $table->string('payment_modality', 50)->default('unique')->after('registration_fee')->comment('unique, mensuel, trimestriel, semestriel');
            $table->integer('number_of_installments')->default(1)->after('payment_modality')->comment('Nombre d\'échéances');
            $table->decimal('installment_amount', 10, 2)->nullable()->after('number_of_installments')->comment('Montant par échéance (calculé automatiquement)');
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropColumn([
                'total_tuition',
                'registration_fee',
                'payment_modality',
                'number_of_installments',
                'installment_amount',
            ]);
        });
    }
};
