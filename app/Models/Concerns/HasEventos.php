<?php

namespace App\Models\Concerns;

use App\Models\Evento;
use App\Models\Usuario;
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

    public function registrarEvento(?Usuario $autor, string $acao, ?string $nota = null): Evento
    {
        return $this->eventos()->create([
            'id_usuario' => $autor?->id,
            'acao' => $acao,
            'nota' => (string) $nota,
        ]);
    }
}
