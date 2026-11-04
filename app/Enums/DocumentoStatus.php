<?php

namespace App\Enums;

/**
 * Etapas do documento de estágio, na ordem das aprovações.
 */
enum DocumentoStatus: string
{
    case Rascunho = 'Rascunho';
    case AnaliseOrientador = 'Em análise: orientador';
    case AnaliseEmpresa = 'Em análise: empresa';
    case AnaliseCoordenacao = 'Em análise: coordenação';
    case Aprovado = 'Aprovado';
}
