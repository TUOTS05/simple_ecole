<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Règlement intérieur versionné : publier une modification crée une nouvelle version,
        // que les parents doivent accepter à nouveau (les acceptations restent liées à la
        // version lue, pour garder la preuve de ce qui a été accepté).
        Schema::create('school_regulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('requires_acceptance')->default(true);
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'version']);
        });

        Schema::create('regulation_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_regulation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('accepted_at');

            $table->unique(['school_regulation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulation_acceptances');
        Schema::dropIfExists('school_regulations');
    }
};
