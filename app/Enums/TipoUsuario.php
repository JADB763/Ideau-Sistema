<?php

namespace App\Enums;

enum TipoUsuario: string
{
    case Aluno = 'Aluno';
    case Professor = 'Professor';
    case Coordenacao = 'Biblioteca/Coordenação';
}
