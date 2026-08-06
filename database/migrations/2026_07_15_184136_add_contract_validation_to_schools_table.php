<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'validation_token')) {
                $table->string('validation_token')->nullable()->after('status');
            }
            if (!Schema::hasColumn('schools', 'contract_validated_at')) {
                $table->timestamp('contract_validated_at')->nullable()->after('validation_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['validation_token', 'contract_validated_at']);
        });
    }
};