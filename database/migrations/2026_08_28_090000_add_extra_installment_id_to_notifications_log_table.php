<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->unsignedBigInteger('extra_installment_id')->nullable()->after('installment_id');
            $table->foreign('extra_installment_id')->references('id')->on('extra_installments')->nullOnDelete();
            $table->index(['school_id', 'extra_installment_id', 'type', 'category'], 'notifications_log_extra_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->dropForeign(['extra_installment_id']);
            $table->dropIndex('notifications_log_extra_lookup_index');
            $table->dropColumn('extra_installment_id');
        });
    }
};
