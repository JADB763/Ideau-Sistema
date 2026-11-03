<?php

namespace App\Policies;

use App\Enums\TccStatus;
use App\Models\Tcc;
use App\Models\Usuario;

/**
 * Visibilidade. As ações de cada etapa do fluxo ficam no serviço do TCC.
 */
class TccPolicy
{
    /**
     * A listagem é filtrada por Tcc::visivelPara().
     */
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Tcc $tcc): bool
    {
        return Tcc::whereKey($tcc->getKey())->visivelPara($usuario)->exists();
    }

    /**
     * O aluno propõe o próprio TCC; a coordenação, para qualquer aluno.
     */
    public function create(Usuario $usuario): bool
    {
        return $usuario->isAluno() || $usuario->isCoordenacao();
    }

    /**
     * A proposta só é editável antes de solicitar a orientação.
     */
    public function update(Usuario $usuario, Tcc $tcc): bool
    {
        return $tcc->status === TccStatus::Proposta
            && ($usuario->isCoordenacao() || $usuario->id === $tcc->id_aluno);
    }
}
