<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('contracts', 'max_teachers') && ! Schema::hasColumn('contracts', 'max_users')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('max_teachers', 'max_users');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'max_users') && ! Schema::hasColumn('contracts', 'max_teachers')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('max_users', 'max_teachers');
            });
        }
    }
};
