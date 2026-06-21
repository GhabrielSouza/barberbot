<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGENDA — agendamentos e conclusão
 *
 * O canônico do front usa { date: 'YYYY-MM-DD', time: 'HH:MM', end: 'HH:MM', dur: '45min' }.
 * Aqui normalizamos para `starts_at` (timestamp) e `ends_at` (timestamp) — queries
 * de "agenda da semana", "conflito de horário" e "overlap por staff" ficam triviais
 * em SQL. Mantemos `dur_minutes` pra exibir no front igual ao canônico.
 *
 * IMPORTANTE: staff_id pode ser nulo durante criação (a IA do chat sugere
 * profissional). Quando o booking_request é aceito, o front manda o staffId.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
            $table->string('category_slug', 30);          // denormalizado p/ histórico (serviço pode mudar de cat)
            $table->timestamp('starts_at');               // início absoluto
            $table->timestamp('ends_at');                 // fim absoluto
            $table->unsignedSmallInteger('dur_minutes');  // 45 | 60 | 50 ...
            $table->decimal('price', 10, 2);              // preço cobrado (pode divergir do preço atual do serviço)
            $table->string('status', 20)->default('pending');
                // done | confirmed | pending | cancelled
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->string('payment_method', 20)->nullable(); // pix|cartao|dinheiro (setado no conclude)
            $table->uuid('booking_request_id')->nullable(); // FK lógica → booking_requests.id
            $table->timestamps();

            // índice principal: agenda por staff no tempo
            $table->index(['staff_id', 'starts_at']);
            // agenda por dia (dashboard, "agendamentos de hoje")
            $table->index(['company_id', 'starts_at']);
            // filtro por status
            $table->index(['company_id', 'status']);
        });

        // Histórico de pagamentos por agendamento.
        // Normalmente 1 linha (no conclude), mas o esquema permite múltiplos
        // (sinal + restante, por ex.). Útil pra conciliação futura com PIX.
        Schema::create('appointment_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->string('method', 20);     // pix|cartao|dinheiro
            $table->decimal('amount', 10, 2);
            $table->uuid('transaction_id')->nullable(); // FK lógica → transactions.id
            $table->timestamps();

            $table->index('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_payments');
        Schema::dropIfExists('appointments');
    }
};