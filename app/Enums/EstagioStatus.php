<?php

namespace App\Enums;

enum EstagioStatus: string
{
    case DocumentosIncompletos = 'Documentos Incompletos';
    case DocumentosEntregues = 'Documentos Entregues';
    case NaoAutorizado = 'Não autorizado';
    case Finalizado = 'Finalizado';
}
