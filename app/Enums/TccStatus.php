<?php

namespace App\Enums;

/**
 * Etapas do TCC, na ordem do fluxo.
 */
enum TccStatus: string
{
    case Proposta = 'Proposta';
    case AguardandoOrientador = 'Aguardando orientador';
    case AguardandoCoordenacao = 'Aguardando coordenação';
    case EmOrientacao = 'Em orientação';
    case VersaoEmAnalise = 'Versão em análise';
    case LiberadoParaDefesa = 'Liberado para defesa';
    case DefesaSolicitada = 'Defesa solicitada';
    case DefesaAutorizada = 'Defesa autorizada';
    case DefesaAgendada = 'Defesa agendada';
    case Avaliado = 'Avaliado';
    case Concluido = 'Concluído';
}
