<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * VIEWS — adaptam o modelo interno (transactions unificado) ao contrato
 * canônico que o front espera:
 *
 *   GET /finance/sales     → SELECT * FROM v_sales
 *   GET /finance/expenses  → SELECT * FROM v_expenses
 *
 * Assim a API não precisa fazer transformação em PHP — a view já entrega
 * o shape { id, label, note/value, date, method } que o adapter do front consome.
 */
return new class extends Migration {
    public function up(): void
    {
        // Vendas (kind = 'in') — sem itens aqui, só cabeçalho (igual ao canônico)
        DB::statement("
            CREATE OR REPLACE VIEW v_sales AS
            SELECT
                t.id,
                t.label,
                COALESCE(t.note, '')        AS note,
                t.value,
                t.method,
                t.occurred_at               AS date
            FROM transactions t
            WHERE t.kind = 'in'
        ");

        // Despesas (kind = 'out')
        DB::statement("
            CREATE OR REPLACE VIEW v_expenses AS
            SELECT
                t.id,
                t.label,
                COALESCE(t.note, '')        AS note,
                t.value,
                t.occurred_at               AS date
            FROM transactions t
            WHERE t.kind = 'out'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_sales');
        DB::statement('DROP VIEW IF EXISTS v_expenses');
    }
};