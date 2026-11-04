<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TIPOS = ['Plano de atividades', 'Relatório de estágio', 'Ficha de avaliação', 'Solicitação de encerramento'];

    private const ETAPAS = ['Rascunho', 'Em análise: orientador', 'Em análise: empresa', 'Em análise: coordenação', 'Aprovado'];

    /**
     * Documentos de estágio gerados a partir dos modelos, com versões preservadas.
     */
    public function up(): void
    {
        Schema::create('DocumentoEstagio', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('id_estagio');
            $table->integer('id_solicitacao')->nullable();
            $table->enum('tipo', self::TIPOS);
            $table->enum('status', self::ETAPAS)->default('Rascunho');
            $table->text('observacoes')->nullable();
            $table->unsignedInteger('revisao')->default(1);
            $table->dateTime('criado_em')->useCurrent();
            $table->dateTime('atualizado_em')->useCurrent();

            $table->foreign('id_estagio', 'fk_documento_estagio')->references('id')->on('Estagio')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_solicitacao', 'fk_documento_solicitacao')->references('id')->on('Solicitacao')
                ->cascadeOnUpdate()->nullOnDelete();
        });

        // Cada versão guarda os dados do estágio e o texto preenchido na data em que foi criada.
        Schema::create('DocumentoVersao', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('id_documento');
            $table->unsignedInteger('numero');
            $table->enum('status', self::ETAPAS)->default('Rascunho');
            $table->json('snapshot');
            $table->text('observacoes')->nullable();
            $table->string('modelo', 80);
            $table->char('modelo_hash', 64);
            $table->longText('texto_preenchido');
            $table->dateTime('criado_em')->useCurrent();

            $table->unique(['id_documento', 'numero'], 'uq_documento_versao');
            $table->foreign('id_documento', 'fk_versao_documento')->references('id')->on('DocumentoEstagio')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DocumentoVersao');
        Schema::dropIfExists('DocumentoEstagio');
    }
};
