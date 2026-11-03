<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespostaSolicitacao extends Model
{
    protected $table = 'RespostaSolicitacao';

    const CREATED_AT = 'data';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_solicitacao',
        'id_usuario',
        'parecer',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class, 'id_solicitacao');
    }

    /**
     * Quem respondeu a solicitação.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
