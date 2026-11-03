<?php

namespace App\Policies;

use App\Models\DocumentoEstagio;
use App\Models\Estagio;
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

    /**
     * O aluno gera documentos dos próprios estágios; a coordenação, de qualquer estágio.
     * Uso: $usuario->can('create', [DocumentoEstagio::class, $estagio]).
     */
    public function create(Usuario $usuario, ?Estagio $estagio = null): bool
    {
        return $usuario->isCoordenacao()
            || ($usuario->isAluno() && ($estagio === null || $estagio->id_aluno === $usuario->id));
    }
}
