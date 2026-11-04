<?php

namespace App\Models;

use App\Enums\StatusAprovacao;
use App\Enums\TccStatus;
use App\Enums\TipoLog;
use App\Models\Concerns\HasEventos;
use Database\Factories\TccFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tcc extends Model
{
    /** @use HasFactory<TccFactory> */
    use HasEventos, HasFactory;

    protected $table = 'TCC';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_aluno',
        'id_professor_orientador',
        'titulo',
        'resumo',
        'status',
        'aprovado',
        'id_versao_liberada',
        'revisao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TccStatus::class,
            'aprovado' => StatusAprovacao::class,
            'revisao' => 'integer',
        ];
    }

    /**
     * Coordenação vê todos; aluno, os próprios; professor, os que orienta e os das bancas de que participa.
     */
    public function scopeVisivelPara(Builder $query, Usuario $usuario): void
    {
        if ($usuario->isAluno()) {
            $query->where('id_aluno', $usuario->id);
        } elseif ($usuario->isProfessor()) {
            $query->where(fn (Builder $q) => $q
                ->where('id_professor_orientador', $usuario->id)
                ->orWhereHas('defesas.banca.participantes', fn (Builder $p) => $p->where('id_professor', $usuario->id)));
        } elseif (! $usuario->isCoordenacao()) {
            $query->whereRaw('1 = 0');
        }
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_aluno');
    }

    public function orientador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_professor_orientador');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TccLog::class, 'id_tcc');
    }

    /**
     * Versões do TCC com PDF, da mais antiga para a mais recente.
     */
    public function versoes(): HasMany
    {
        return $this->logs()->where('tipo', TipoLog::Versao)->orderBy('id');
    }

    /**
     * Registros de orientação (reuniões).
     */
    public function orientacoes(): HasMany
    {
        return $this->logs()->where('tipo', TipoLog::Orientacao)->orderBy('data_orientacao');
    }

    public function versaoLiberada(): BelongsTo
    {
        return $this->belongsTo(TccLog::class, 'id_versao_liberada');
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(Solicitacao::class, 'id_tcc');
    }

    public function defesas(): HasMany
    {
        return $this->hasMany(Defesa::class, 'id_tcc');
    }

    public function defesaAtual(): HasOne
    {
        return $this->hasOne(Defesa::class, 'id_tcc')->latestOfMany();
    }
}
