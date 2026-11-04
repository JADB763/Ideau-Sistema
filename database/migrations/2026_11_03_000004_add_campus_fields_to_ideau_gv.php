<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etapas do TCC trazidas do Campus.
     */
    private const ETAPAS_TCC = [
        'Proposta', 'Aguardando orientador', 'Aguardando coordenação', 'Em orientação', 'Versão em análise',
        'Liberado para defesa', 'Defesa solicitada', 'Defesa autorizada', 'Defesa agendada', 'Avaliado', 'Concluído',
    ];

    /**
     * Colunas que o Campus usa e o ideau_gv não tinha.
     */
    public function up(): void
    {
        Schema::table('Usuario', function (Blueprint $table) {
            $table->string('matricula', 30)->nullable();
            $table->string('curso', 180)->nullable();
            $table->string('instituicao', 180)->nullable();
            $table->string('area', 180)->nullable();
            $table->boolean('ativo')->default(true);
        });

        Schema::table('Empresa', function (Blueprint $table) {
            $table->string('email', 254)->nullable();
            $table->string('representante', 180)->nullable();
            $table->text('endereco')->nullable();
        });

        Schema::table('Estagio', function (Blueprint $table) {
            $table->integer('id_professor_orientador')->nullable()->after('id_aluno');
            $table->string('supervisor', 180)->nullable();
            $table->string('supervisor_email', 254)->nullable();
            $table->decimal('horas_semanais', 4, 1)->nullable();
            $table->text('atividades')->nullable();

            $table->foreign('id_professor_orientador', 'fk_estagio_orientador')->references('id')->on('Usuario')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('TCC', function (Blueprint $table) {
            $table->text('resumo')->nullable()->after('titulo');
            $table->enum('status', self::ETAPAS_TCC)->default('Proposta');
            $table->integer('id_versao_liberada')->nullable();
            $table->unsignedInteger('revisao')->default(1);

            $table->foreign('id_versao_liberada', 'fk_tcc_versao_liberada')->references('id')->on('TCCLog')
                ->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::table('Solicitacao', function (Blueprint $table) {
            $table->integer('id_tcc')->nullable()->after('id_aluno');
            $table->integer('id_estagio')->nullable()->after('id_tcc');
            $table->text('descricao')->nullable();

            $table->foreign('id_tcc', 'fk_solicitacao_tcc')->references('id')->on('TCC')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_estagio', 'fk_solicitacao_estagio')->references('id')->on('Estagio')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('RespostaSolicitacao', function (Blueprint $table) {
            $table->integer('id_usuario')->nullable()->after('id_solicitacao');
            $table->text('parecer')->nullable();

            $table->foreign('id_usuario', 'fk_resposta_usuario')->references('id')->on('Usuario')
                ->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::table('Defesa', function (Blueprint $table) {
            $table->string('local', 1000)->nullable();
        });

        Schema::table('Ata', function (Blueprint $table) {
            $table->decimal('nota_minima', 4, 2)->nullable();
            $table->dateTime('criado_em')->useCurrent();
            $table->dateTime('homologada_em')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Ata', function (Blueprint $table) {
            $table->dropColumn(['nota_minima', 'criado_em', 'homologada_em']);
        });

        Schema::table('Defesa', function (Blueprint $table) {
            $table->dropColumn('local');
        });

        Schema::table('RespostaSolicitacao', function (Blueprint $table) {
            $table->dropForeign('fk_resposta_usuario');
            $table->dropColumn(['id_usuario', 'parecer']);
        });

        Schema::table('Solicitacao', function (Blueprint $table) {
            $table->dropForeign('fk_solicitacao_tcc');
            $table->dropForeign('fk_solicitacao_estagio');
            $table->dropColumn(['id_tcc', 'id_estagio', 'descricao']);
        });

        Schema::table('TCC', function (Blueprint $table) {
            $table->dropForeign('fk_tcc_versao_liberada');
            $table->dropColumn(['resumo', 'status', 'id_versao_liberada', 'revisao']);
        });

        Schema::table('Estagio', function (Blueprint $table) {
            $table->dropForeign('fk_estagio_orientador');
            $table->dropColumn(['id_professor_orientador', 'supervisor', 'supervisor_email', 'horas_semanais', 'atividades']);
        });

        Schema::table('Empresa', function (Blueprint $table) {
            $table->dropColumn(['email', 'representante', 'endereco']);
        });

        Schema::table('Usuario', function (Blueprint $table) {
            $table->dropColumn(['matricula', 'curso', 'instituicao', 'area', 'ativo']);
        });
    }
};
