<?php

namespace App\Models;

use App\Enums\FuncaoBanca;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot Banca x Usuario (chave primária composta id_banca + id_professor).
 */
class ParticipanteBanca extends Pivot
{
    protected $table = 'ParticipanteBanca';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_banca',
        'id_professor',
        'funcao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'funcao' => FuncaoBanca::class,
        ];
    }

    public function banca(): BelongsTo
    {
        return $this->belongsTo(Banca::class, 'id_banca');
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_professor');
    }
}
