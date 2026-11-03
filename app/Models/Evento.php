<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Evento extends Model
{
    protected $table = 'Evento';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_usuario',
        'acao',
        'nota',
    ];

    /**
     * Quem registrou a movimentação.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    /**
     * Registro movimentado (estágio, TCC, documento, solicitação...).
     */
    public function entidade(): MorphTo
    {
        return $this->morphTo();
    }
}
