<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * commission_methods — catálogo dos tipos de divisão de comissão (schema
 * public, seed fixo). Só para a UI listar as opções; a lógica fica no
 * backend. Ver MODELO-BANCO-DE-DADOS.md §2.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // per_attendance|equal_split|custom_percent
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('commission_methods')->insert([
            [
                'id' => (string) Str::uuid(),
                'code' => 'per_attendance',
                'name' => 'Cada um recebe o que atende',
                'description' => 'Soma de agendamentos concluídos e vendas por membro da equipe.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'equal_split',
                'name' => 'Divisão igualitária',
                'description' => 'Lucro do período dividido igualmente entre membros ativos.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'custom_percent',
                'name' => 'Percentual customizado',
                'description' => 'Percentual fixo por membro, definido em commission_shares.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_methods');
    }
};
