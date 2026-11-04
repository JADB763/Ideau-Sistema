<?php

namespace App\Policies;

use App\Models\Solicitacao;
use App\Models\Usuario;

/**
 * Visibilidade. Responder solicitações fica no serviço de cada fluxo.
 */
class SolicitacaoPolicy
{
    /**
     * A listagem é filtrada por Solicitacao::visivelPara().
     */
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return Solicitacao::whereKey($solicitacao->getKey())->visivelPara($usuario)->exists();
    }
}
