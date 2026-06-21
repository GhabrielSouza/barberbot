<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FINANCEIRO — transações, despesas, vendas, config PIX e cache de receita semanal
 *
 * Modelo unificado: `transactions` cobre in (vendas/receitas) e out (despesas).
 * Para vendas com múltiplos itens (ex.: "Pomada modeladora ×2"), os itens
 * ficam em `transaction_items` — assim o Caixa mostra a soma sem perder detalhe.
 *
 * Separei `sales` e `expenses` em views (mais abaixo) pra bater com o
 * contrato canônico do front (`GET /finance/sales`, `GET /finance/expenses`).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('kind', 5);                  // in|out
            $table->string('label');                    // "Corte + Barba"
            $table->string('note')->nullable();         // "André Costa · pix"
            $table->decimal('value', 12, 2);            // sinal respeitando kind (negativo se out)
            $table->string('method', 20)->nullable();   // pix|cartao|dinheiro|""
            $table->string('category', 30)->nullable(); // "venda de produto" | "aluguel" | ...
            $table->uuid('appointment_id')->nullable(); // quando vem de agendamento concluído
            $table->uuid('client_id')->nullable();
            $table->uuid('staff_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'kind', 'occurred_at']);
            $table->index(['company_id', 'occurred_at']);
        });

        // Itens de uma transação (vendas com N produtos/serviços).
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('kind', 10);             // service|product
            $table->uuid('item_id');                // FK lógica → services.id ou products.id
            $table->string('item_name');            // snapshot do nome no momento da venda
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->index('transaction_id');
        });

        // Config PIX — singleton por tenant (1 linha por empresa).
        Schema::create('pix_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->unique()->constrained('companies')->cascadeOnDelete();
            $table->string('key');                          // "studio.rafa@pix.com.br"
            $table->string('type', 20);                     // CPF|E-mail|Telefone|Aleatória
            $table->string('holder_name')->nullable();      // p/ gerar BR Code
            $table->string('holder_city')->nullable();      // p/ gerar BR Code
            $table->timestamps();
        });

        // Cache da receita semanal (regenerado por job/job de leitura).
        // Deriva de transactions por dia, mas cacheia pra o dashboard
        // não precisar agregar tudo em cada GET /dashboard.
        Schema::create('week_revenues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('revenue', 12, 2)->default(0);
            $table->unsignedInteger('appointments_count')->default(0);
            $table->boolean('hot')->default(false);         // destaque visual no gráfico
            $table->timestamps();

            $table->unique(['company_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('week_revenues');
        Schema::dropIfExists('pix_config');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};