<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Banco em que os testes podem rodar. RefreshDatabase apaga todas as tabelas dele.
     */
    private const BANCO_DE_TESTES = 'ideau_gv_test';

    /**
     * Recusa rodar fora do banco de testes, antes de qualquer trait limpar o banco.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $conexao = $app['config']['database.default'];
        $banco = $app['config']["database.connections.$conexao.database"];

        if ($banco !== self::BANCO_DE_TESTES) {
            throw new RuntimeException('Testes só rodam no banco '.self::BANCO_DE_TESTES.", não em \"$banco\". Confira phpunit.xml e o cache de configuração.");
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
