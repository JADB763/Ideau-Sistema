<?php

namespace App\Policies;

use App\Models\Estagio;
use App\Models\Usuario;

class EstagioPolicy
{
    /**
     * A listagem é filtrada por Estagio::visivelPara().
     */
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Estagio $estagio): bool
    {
        return Estagio::whereKey($estagio->getKey())->visivelPara($usuario)->exists();
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->isCoordenacao();
    }

    public function update(Usuario $usuario, Estagio $estagio): bool
    {
        return $usuario->isCoordenacao();
    }
}
