<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Defesa extends Model
{
    protected $table = 'Defesa';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_aluno',
        'id_tcc',
        'id_solicitacao',
        'agendado_para',
        'local',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'agendado_para' => 'datetime',
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

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class, 'id_solicitacao');
    }

    public function banca(): HasOne
    {
        return $this->hasOne(Banca::class, 'id_defesa');
    }
}
