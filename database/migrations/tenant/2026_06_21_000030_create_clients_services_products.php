<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CLIENTES, SERVIÇOS E PRODUTOS
 *
 * Catálogo comercial do tenant. Tudo amarrado em company_id (multi-empresa
 * dentro do mesmo schema — útil se um assinante tiver filiais; hoje 1:1).
 */
return new class extends Migration {
    public function up(): void
    {
        // Clientes
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('initials', 5);
            $table->string('color', 9);
            $table->string('phone', 20);
            $table->string('tag')->nullable();           // "recorrente" | "nova cliente" | ""
            $table->unsignedInteger('visits')->default(0);
            $table->decimal('spent', 12, 2)->default(0);
            $table->decimal('rating', 3, 1)->default(0); // 0.0..5.0
            $table->timestamp('last_visit_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'name']);
            $table->index('phone');
        });

        // Categorias de serviço — enum-like (barbearia, estetica, spa, fitness, novo)
        Schema::create('service_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('slug', 30)->unique();   // "barbearia"
            $table->string('label');                // "Barbearia"
            $table->timestamps();
        });

        // Serviços
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('dur');         // "45min" | "1h" | "1h30" (canônico do front)
            $table->unsignedSmallInteger('dur_minutes'); // normalizado p/ cálculo de agenda
            $table->decimal('price', 10, 2);
            $table->foreignUuid('category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'active']);
        });

        // Produtos (venda de insumo/mercadoria)
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->string('cat')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('clients');
    }
};