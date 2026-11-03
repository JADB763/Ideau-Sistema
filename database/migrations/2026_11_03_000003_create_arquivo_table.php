<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PDFs enviados. TCCLog.arquivo era um int sem tabela de destino.
     */
    public function up(): void
    {
        Schema::create('Arquivo', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('id_usuario')->nullable();
            $table->string('nome', 255);
            $table->string('caminho', 255)->unique();
            $table->char('sha256', 64);
            $table->unsignedBigInteger('tamanho_bytes');
            $table->dateTime('criado_em')->useCurrent();

            $table->foreign('id_usuario', 'fk_arquivo_usuario')->references('id')->on('Usuario')
                ->cascadeOnUpdate()->nullOnDelete();
        });

        // Cada log é uma versão do TCC (com PDF) ou um registro de orientação (sem PDF).
        Schema::table('TCCLog', function (Blueprint $table) {
            $table->enum('tipo', ['Versão', 'Orientação'])->default('Versão')->after('id_tcc');
            $table->date('data_orientacao')->nullable()->after('tipo');
            $table->integer('arquivo')->nullable()->change();

            $table->foreign('arquivo', 'fk_log_arquivo')->references('id')->on('Arquivo')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('TCCLog', function (Blueprint $table) {
            $table->dropForeign('fk_log_arquivo');
            $table->dropIndex('fk_log_arquivo');
            $table->integer('arquivo')->nullable(false)->change();
            $table->dropColumn(['tipo', 'data_orientacao']);
        });

        Schema::dropIfExists('Arquivo');
    }
};
