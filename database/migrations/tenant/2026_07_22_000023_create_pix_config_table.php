<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pix_config — configuração pix, 1 linha por tenant (schema tenant_<id>).
 * Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pix_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->string('key_type', 20);
            $table->string('holder_name')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pix_config');
    }
};
