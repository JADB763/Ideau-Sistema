<?php

namespace App\Models;

use App\Enums\StatusAprovacao;
use App\Enums\TipoSolicitacao;
use App\Models\Concerns\HasEventos;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Coordenação vê todas; aluno, as próprias; professor, as dos TCCs e estágios que orienta.
     */
    public function scopeVisivelPara(Builder $query, Usuario $usuario): void
    {
        if ($usuario->isAluno()) {
            $query->where('id_aluno', $usuario->id);
        } elseif ($usuario->isProfessor()) {
            $query->where(fn (Builder $q) => $q
                ->whereHas('tcc', fn (Builder $t) => $t->where('id_professor_orientador', $usuario->id))
                ->orWhereHas('estagio', fn (Builder $e) => $e->where('id_professor_orientador', $usuario->id)));
        } elseif (! $usuario->isCoordenacao()) {
            $query->whereRaw('1 = 0');
        }
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
