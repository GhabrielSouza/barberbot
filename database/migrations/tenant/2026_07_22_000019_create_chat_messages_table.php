<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * chat_messages — mensagens de uma conversa (schema tenant_<id>).
 * `pix_payload` guarda a cobrança pix embutida na mensagem, quando houver.
 * Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->string('direction', 5); // in|out
            $table->text('text')->nullable();
            $table->boolean('is_system')->default(false);
            $table->jsonb('pix_payload')->nullable();
            $table->timestamp('sent_at');

            $table->index(['chat_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
