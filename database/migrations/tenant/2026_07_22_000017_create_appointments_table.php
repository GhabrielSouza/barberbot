<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * appointments — agendamentos, entidade central (schema tenant_<id>).
 * `service_name` e `price` guardam o nome/preço "congelados" no momento do
 * agendamento, conforme contrato canônico. Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('team_member_id')->constrained('team_members')->restrictOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
            $table->string('service_name');
            $table->decimal('price', 10, 2);
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('pending'); // pending|confirmed|done|cancelled
            $table->string('payment_method', 20)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['team_member_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
