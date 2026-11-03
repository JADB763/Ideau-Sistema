<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas cujo id não era AUTO_INCREMENT.
     */
    private const SEM_AUTO_INCREMENT = ['Estagio', 'TCCLog', 'Solicitacao', 'Banca', 'Avaliacao', 'Ata'];

    /**
     * Corrige chaves do ideau_gv.
     */
    public function up(): void
    {
        // Banca.id -> Ata.id_banca e Ata.id_banca -> Banca.id formavam um ciclo:
        // nenhuma das duas linhas podia ser inserida primeiro.
        Schema::table('Banca', function (Blueprint $table) {
            $table->dropForeign('Banca_ibfk_1');
        });

        // Alguns desses ids são referenciados por outras tabelas; o MariaDB só
        // permite alterá-los com a verificação de chaves desligada.
        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::SEM_AUTO_INCREMENT as $table) {
                DB::statement("ALTER TABLE `$table` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
            }
        });

        Schema::table('TCC', function (Blueprint $table) {
            $table->foreign('id_aluno', 'fk_tcc_aluno')->references('id')->on('Usuario')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_professor_orientador', 'fk_tcc_orientador')->references('id')->on('Usuario')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        // Restrict evita cascata em ciclo com Solicitacao.id_resposta (ON DELETE SET NULL).
        Schema::table('RespostaSolicitacao', function (Blueprint $table) {
            $table->foreign('id_solicitacao', 'fk_resposta_solicitacao')->references('id')->on('Solicitacao')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('RespostaSolicitacao', function (Blueprint $table) {
            $table->dropForeign('fk_resposta_solicitacao');
            $table->dropIndex('fk_resposta_solicitacao');
        });

        Schema::table('TCC', function (Blueprint $table) {
            $table->dropForeign('fk_tcc_aluno');
            $table->dropForeign('fk_tcc_orientador');
            $table->dropIndex('fk_tcc_aluno');
            $table->dropIndex('fk_tcc_orientador');
        });

        Schema::withoutForeignKeyConstraints(function () {
            foreach (self::SEM_AUTO_INCREMENT as $table) {
                DB::statement("ALTER TABLE `$table` MODIFY `id` INT(11) NOT NULL");
            }
            DB::statement('ALTER TABLE `Banca` ADD CONSTRAINT `Banca_ibfk_1` FOREIGN KEY (`id`) REFERENCES `Ata` (`id_banca`) ON DELETE NO ACTION ON UPDATE CASCADE');
        });
    }
};
