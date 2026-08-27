<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('sms_enabled')->default(false)->after('phone');
            $table->string('orange_sms_api_url')->nullable()->after('sms_enabled');
            $table->string('orange_sms_client_id')->nullable()->after('orange_sms_api_url');
            $table->string('orange_sms_client_secret')->nullable()->after('orange_sms_client_id');
            $table->string('orange_sms_sender_name', 11)->nullable()->after('orange_sms_client_secret');
            $table->text('sms_absence_template')->nullable()->after('orange_sms_sender_name');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'sms_enabled', 'orange_sms_api_url', 'orange_sms_client_id',
                'orange_sms_client_secret', 'orange_sms_sender_name', 'sms_absence_template',
            ]);
        });
    }
};
