<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // L'école concernée
            $table->foreignId('school_id')
                ->constrained('schools')
                ->onDelete('cascade');

            // Le parent qui envoie le message
            $table->foreignId('sender_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Contenu du message
            $table->string('subject');
            $table->text('message');

            // Réponse de l'école (optionnelle)
            $table->text('reply')->nullable();

            // Statuts
            $table->boolean('is_read')->default(false);
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
