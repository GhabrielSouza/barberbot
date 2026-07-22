<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sales — vendas do caixa (schema tenant_<id>). `service_id`/`product_id` são
 * nullable — só um dos dois é preenchido, conforme `kind`. `team_member_id`
 * é necessário para a comissão `per_attendance` funcionar também em produtos.
 * Venda de produto decrementa `products.stock` (lógica no backend).
 * Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind', 10); // service|product
            $table->foreignUuid('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('payment_method', 20)->nullable();
            $table->foreignUuid('team_member_id')->constrained('team_members')->restrictOnDelete();
            $table->date('date');
            $table->timestamp('created_at')->nullable();

            $table->index(['team_member_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
