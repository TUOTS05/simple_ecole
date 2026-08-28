<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extras', function (Blueprint $table) {
            $table->string('location')->nullable()->after('responsible_id');
            $table->time('daycare_closing_time')->nullable()->after('location');
            $table->decimal('overage_rate_per_minute', 10, 2)->nullable()->after('daycare_closing_time');
        });
    }

    public function down(): void
    {
        Schema::table('extras', function (Blueprint $table) {
            $table->dropColumn(['location', 'daycare_closing_time', 'overage_rate_per_minute']);
        });
    }
};
