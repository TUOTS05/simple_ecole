<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extra_attendances', function (Blueprint $table) {
            $table->timestamp('overage_billed_at')->nullable()->after('overage_amount');
        });
    }

    public function down(): void
    {
        Schema::table('extra_attendances', function (Blueprint $table) {
            $table->dropColumn('overage_billed_at');
        });
    }
};
