<?php

namespace App\Enums;

enum TipoSolicitacao: string
{
    case Documento = 'Solicitação de documento';
    case TrocaDeProfessor = 'Solicitação de troca de professor';
    case Orientador = 'Solicitação de orientador';
    case Defesa = 'Solicitação de defesa';
}
