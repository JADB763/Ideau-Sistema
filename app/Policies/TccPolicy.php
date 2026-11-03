<?php

namespace App\Policies;

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
}
