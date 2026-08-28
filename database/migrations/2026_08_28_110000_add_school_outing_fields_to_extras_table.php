<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extras', function (Blueprint $table) {
            $table->string('destination', 150)->nullable()->after('location');
            $table->date('registration_deadline')->nullable()->after('destination');
            $table->boolean('includes_transport')->default(false)->after('registration_deadline');
            $table->boolean('requires_parental_authorization')->default(false)->after('includes_transport');
        });

        Schema::table('extra_subscriptions', function (Blueprint $table) {
            $table->boolean('parental_authorization_signed')->default(false)->after('notes');
            $table->timestamp('parental_authorization_signed_at')->nullable()->after('parental_authorization_signed');
        });
    }

    public function down(): void
    {
        Schema::table('extras', function (Blueprint $table) {
            $table->dropColumn(['destination', 'registration_deadline', 'includes_transport', 'requires_parental_authorization']);
        });

        Schema::table('extra_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['parental_authorization_signed', 'parental_authorization_signed_at']);
        });
    }
};
