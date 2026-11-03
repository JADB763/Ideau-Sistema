<?php

namespace App\Models;

use App\Enums\StatusAprovacao;
use App\Enums\TipoSolicitacao;
use App\Models\Concerns\HasEventos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Solicitacao extends Model
{
    use HasEventos;

    protected $table = 'Solicitacao';

    const CREATED_AT = 'data_solicitacao';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tipo_de_solicitacao',
        'id_aluno',
        'id_tcc',
        'id_estagio',
        'id_resposta',
        'status',
        'descricao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_de_solicitacao' => TipoSolicitacao::class,
            'status' => StatusAprovacao::class,
        ];
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_aluno');
    }

    public function tcc(): BelongsTo
    {
        return $this->belongsTo(Tcc::class, 'id_tcc');
    }

    public function estagio(): BelongsTo
    {
        return $this->belongsTo(Estagio::class, 'id_estagio');
    }

    public function resposta(): BelongsTo
    {
        return $this->belongsTo(RespostaSolicitacao::class, 'id_resposta');
    }

    public function defesa(): HasOne
    {
        return $this->hasOne(Defesa::class, 'id_solicitacao');
    }

    public function documento(): HasOne
    {
        return $this->hasOne(DocumentoEstagio::class, 'id_solicitacao');
    }
}
