<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige tipos de coluna do ideau_gv que impedem o uso do sistema.
     */
    public function up(): void
    {
        // Nome, e-mail e senha estavam como int: não armazenavam texto nem hash de senha.
        Schema::table('Usuario', function (Blueprint $table) {
            $table->string('nome_completo', 180)->change();
            $table->string('email', 254)->change();
            $table->string('senha', 255)->change();
            $table->unique('email');
            $table->unique('cpf');
        });

        // Datas estavam como int.
        Schema::table('Defesa', function (Blueprint $table) {
            $table->dateTime('criado_em')->useCurrent()->change();
            $table->dateTime('agendado_para')->nullable()->change();
        });

        // decimal(10,0) arredondava as notas para inteiro (7,5 virava 8).
        Schema::table('Avaliacao', function (Blueprint $table) {
            $table->decimal('nota', 4, 2)->change();
        });
        Schema::table('Ata', function (Blueprint $table) {
            $table->decimal('media_final', 4, 2)->change();
        });

        // ON UPDATE current_timestamp() alterava a data de envio a cada edição da linha.
        Schema::table('TCCLog', function (Blueprint $table) {
            $table->timestamp('data_enviado')->useCurrent()->change();
        });
        Schema::table('Solicitacao', function (Blueprint $table) {
            $table->timestamp('data_solicitacao')->useCurrent()->change();
        });
        Schema::table('RespostaSolicitacao', function (Blueprint $table) {
            $table->timestamp('data')->useCurrent()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('RespostaSolicitacao', function (Blueprint $table) {
            $table->timestamp('data')->useCurrent()->useCurrentOnUpdate()->change();
        });
        Schema::table('Solicitacao', function (Blueprint $table) {
            $table->timestamp('data_solicitacao')->useCurrent()->useCurrentOnUpdate()->change();
        });
        Schema::table('TCCLog', function (Blueprint $table) {
            $table->timestamp('data_enviado')->useCurrent()->useCurrentOnUpdate()->change();
        });

        Schema::table('Ata', function (Blueprint $table) {
            $table->decimal('media_final', 10, 0)->change();
        });
        Schema::table('Avaliacao', function (Blueprint $table) {
            $table->decimal('nota', 10, 0)->change();
        });

        Schema::table('Defesa', function (Blueprint $table) {
            $table->integer('criado_em')->change();
            $table->integer('agendado_para')->change();
        });

        Schema::table('Usuario', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['cpf']);
            $table->integer('nome_completo')->change();
        });

        // Restaura exatamente int(200), que o Blueprint não gera.
        DB::statement('ALTER TABLE `Usuario` MODIFY `email` INT(200) NOT NULL, MODIFY `senha` INT(200) NOT NULL');
    }
};
