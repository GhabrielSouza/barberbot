<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CHAT — conversas vindas do WhatsApp/Instagram (via Evolution API)
 *
 * Modelo:
 *   - chats: 1 conversa = 1 cliente num canal. Pode ter um booking_request
 *            pendente embutido (representa a "solicitação de agendamento"
 *            que aparece no card e que pode ser aceita/recusada).
 *   - chat_messages: histórico da conversa. Mensagens do cliente (them),
 *            respostas do atendente (me), mensagens de sistema (system=true)
 *            e mensagens com PIX embutido (campo pix JSONB).
 *
 * Origem: WhatsApp → Evolution API → Laravel → grava aqui → Pusher notifica o front.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('channel', 20);               // WhatsApp|Instagram
            $table->string('external_id')->nullable();   // id da conversa no provedor (Evolution)
            $table->string('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedSmallInteger('unread')->default(0);
            $table->string('tag')->nullable();           // "nova cliente" | "recorrente" | ""
            $table->boolean('open')->default(true);
            $table->timestamps();

            // 1 conversa por (cliente, canal)
            $table->unique(['client_id', 'channel']);
            $table->index(['company_id', 'last_message_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->string('from', 5);                    // me|them|system
            $table->text('text')->nullable();
            $table->jsonb('pix')->nullable();            // { amount, key, type } quando é cobrança
            $table->string('external_id')->nullable();   // id no provedor, p/ dedupe
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['chat_id', 'external_id']);  // dedupe por id do provedor
            $table->index(['chat_id', 'sent_at']);
        });

        // Solicitação de agendamento embutida no chat.
        // Quando o atendente aceita → cria appointment + marca handled='accepted'.
        // Quando recusa → marca handled='declined' e o card some.
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignUuid('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('date');                          // data desejada
            $table->time('time');                          // hora desejada
            $table->string('dur');                         // "1h" | "50min"
            $table->decimal('price', 10, 2);
            $table->string('status', 20)->default('pending'); // pending|accepted|declined|expired
            $table->timestamp('handled_at')->nullable();
            $table->uuid('handled_by_user_id')->nullable();
            $table->uuid('appointment_id')->nullable();   // appointment criado no aceite
            $table->timestamps();

            $table->index(['chat_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chats');
    }
};