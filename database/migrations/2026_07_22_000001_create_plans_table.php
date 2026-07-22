<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * plans — catálogo de planos do SaaS (schema public).
 * Cobrança fixo + por membro extra (ver MODELO-BANCO-DE-DADOS.md §2):
 *   total = price_month + max(0, membros_ativos - included_members) * price_per_extra_member
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('price_month', 10, 2);
            $table->integer('included_members');
            $table->decimal('price_per_extra_member', 10, 2);
            $table->jsonb('limits')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
