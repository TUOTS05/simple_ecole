<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le plafond du plan ne couvre pas que les enseignants (comptables, responsables
        // cantine/transport, direction, ...) : on aligne la colonne sur son vrai sens.
        if (Schema::hasColumn('subscription_plans', 'max_teachers') && ! Schema::hasColumn('subscription_plans', 'max_users')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->renameColumn('max_teachers', 'max_users');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_plans', 'max_users') && ! Schema::hasColumn('subscription_plans', 'max_teachers')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->renameColumn('max_users', 'max_teachers');
            });
        }
    }
};
