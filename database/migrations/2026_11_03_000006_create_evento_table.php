<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Histórico de movimentações de qualquer registro (estágio, TCC, documento, solicitação...).
     */
    public function up(): void
    {
        Schema::create('Evento', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('id_usuario')->nullable();
            $table->string('entidade_type', 60);
            $table->integer('entidade_id');
            $table->string('acao', 200);
            $table->text('nota');
            $table->dateTime('criado_em')->useCurrent();

            $table->index(['entidade_type', 'entidade_id', 'criado_em'], 'idx_evento_entidade');
            $table->foreign('id_usuario', 'fk_evento_usuario')->references('id')->on('Usuario')
                ->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Evento');
    }
};
