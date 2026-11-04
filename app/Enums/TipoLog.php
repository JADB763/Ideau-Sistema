<?php

namespace App\Enums;

/**
 * Tipo de registro em TCCLog: versão com PDF ou registro de orientação.
 */
enum TipoLog: string
{
    case Versao = 'Versão';
    case Orientacao = 'Orientação';
}
