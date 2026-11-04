<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ata extends Model
{
    protected $table = 'Ata';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = null;

    /**
     * nota_1, nota_2 e nota_3 são chaves para Avaliacao, não valores de nota.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_banca',
        'nota_1',
        'nota_2',
        'nota_3',
        'media_final',
        'nota_minima',
        'aprovado',
        'homologada_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_final' => 'decimal:2',
            'nota_minima' => 'decimal:2',
            'aprovado' => 'boolean',
            'homologada_em' => 'datetime',
        ];
    }

    public function banca(): BelongsTo
    {
        return $this->belongsTo(Banca::class, 'id_banca');
    }

    public function avaliacao1(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class, 'nota_1');
    }

    public function avaliacao2(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class, 'nota_2');
    }

    public function avaliacao3(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class, 'nota_3');
    }
}
