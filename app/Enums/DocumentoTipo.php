<?php

namespace App\Enums;

enum DocumentoTipo: string
{
    case PlanoDeAtividades = 'Plano de atividades';
    case RelatorioDeEstagio = 'Relatório de estágio';
    case FichaDeAvaliacao = 'Ficha de avaliação';
    case SolicitacaoDeEncerramento = 'Solicitação de encerramento';
}
