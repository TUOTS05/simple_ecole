<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extra_subscriptions', function (Blueprint $table) {
            $table->decimal('original_amount', 12, 2)->nullable()->after('total_amount');
            // individual, family, sibling, promotion, free, scholarship, exceptional
            $table->string('discount_type')->nullable()->after('original_amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_type');
            $table->string('discount_reason', 255)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('extra_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'discount_type', 'discount_amount', 'discount_reason']);
        });
    }
};
