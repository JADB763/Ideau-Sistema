<?php

namespace App\Policies;

use App\Models\DocumentoEstagio;
use App\Models\Usuario;

/**
 * Visibilidade. As ações de cada etapa do fluxo ficam no serviço do documento.
 */
class DocumentoEstagioPolicy
{
    /**
     * A listagem é filtrada por DocumentoEstagio::visivelPara().
     */
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, DocumentoEstagio $documento): bool
    {
        return DocumentoEstagio::whereKey($documento->getKey())->visivelPara($usuario)->exists();
    }
}
