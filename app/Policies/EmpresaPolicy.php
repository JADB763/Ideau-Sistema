<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\Usuario;

/**
 * Empresas são visíveis a todos; só a coordenação cadastra e altera.
 */
class EmpresaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Empresa $empresa): bool
    {
        return true;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->isCoordenacao();
    }

    public function update(Usuario $usuario, Empresa $empresa): bool
    {
        return $usuario->isCoordenacao();
    }
}
