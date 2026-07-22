<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * commission_settings — divisão de comissão escolhida (1 linha por tenant).
 * Valores derivados por query, nada de comissão persistida por transação.
 * Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('method_code'); // FK lógico → public.commission_methods.code
            $table->boolean('applies_to_products')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};
