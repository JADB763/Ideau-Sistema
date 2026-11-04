<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recria a estrutura original do ideau_gv em um banco vazio (ex.: banco de testes).
     * No ideau_gv, onde as tabelas já existem, não faz nada.
     */
    public function up(): void
    {
        if (Schema::hasTable('Usuario')) {
            return;
        }

        DB::unprepared(file_get_contents(database_path('schema/ideau_gv_baseline.sql')));
    }

    /**
     * A estrutura original nunca é removida por rollback.
     */
    public function down(): void
    {
        //
    }
};
