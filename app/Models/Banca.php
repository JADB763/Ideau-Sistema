<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Banca extends Model
{
    protected $table = 'Banca';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_defesa',
    ];

    public function defesa(): BelongsTo
    {
        return $this->belongsTo(Defesa::class, 'id_defesa');
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(ParticipanteBanca::class, 'id_banca');
    }

    /**
     * Professores da banca, com a função (Presidente / Avaliador) no pivot.
     */
    public function professores(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'ParticipanteBanca', 'id_banca', 'id_professor')
            ->using(ParticipanteBanca::class)
            ->withPivot('funcao');
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class, 'id_banca');
    }

    public function ata(): HasOne
    {
        return $this->hasOne(Ata::class, 'id_banca');
    }
}
