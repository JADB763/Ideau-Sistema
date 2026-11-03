<?php

namespace App\Models\Concerns;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Histórico de movimentações do registro (tabela Evento).
 */
trait HasEventos
{
    public function eventos(): MorphMany
    {
        return $this->morphMany(Evento::class, 'entidade')->latest('criado_em')->latest('id');
    }
}
