<?php

namespace App\Policies;

use App\Models\Usuario;

/**
 * Só a coordenação cadastra e altera contas.
 */
class UsuarioPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->isCoordenacao();
    }

    public function view(Usuario $usuario, Usuario $outro): bool
    {
        return $usuario->isCoordenacao() || $usuario->is($outro);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->isCoordenacao();
    }

    public function update(Usuario $usuario, Usuario $outro): bool
    {
        return $usuario->isCoordenacao();
    }
}
