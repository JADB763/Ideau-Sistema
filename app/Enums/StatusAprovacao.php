<?php

namespace App\Enums;

/**
 * Usado em Solicitacao.status e TCC.aprovado.
 */
enum StatusAprovacao: string
{
    case EsperandoResposta = 'Esperando resposta';
    case Aprovado = 'Aprovado';
    case NaoAprovado = 'Não aprovado';
}
