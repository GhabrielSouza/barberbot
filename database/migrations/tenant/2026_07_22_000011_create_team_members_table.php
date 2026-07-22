<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * team_members — membros da equipe (schema tenant_<id>).
 * O front chama de "staff" no contrato de API; o dono da empresa entra
 * como membro com is_admin=true. `initials` é derivado do nome — não persistir.
 * Ver MODELO-BANCO-DE-DADOS.md §3.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->uuid('user_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('public.users')->nullOnDelete();
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
