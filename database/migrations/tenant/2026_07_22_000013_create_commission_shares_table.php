<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * commission_shares — percentual por membro (só p/ method custom_percent).
 * Backend valida soma = 100. Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_member_id')->unique()->constrained('team_members')->cascadeOnDelete();
            $table->decimal('percent', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_shares');
    }
};
