<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * chats — conversas via WhatsApp/Instagram (Evolution API), schema tenant_<id>.
 * `last`/`time` do front derivam da última mensagem — não persistir.
 * Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('channel', 20); // whatsapp|instagram
            $table->string('external_id')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('channel');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
