<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * clients — clientes finais (schema tenant_<id>).
 * `visits`, `spent` e `lastVisit` do front são derivados dos appointments —
 * não persistir. Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('tag')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('color')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
