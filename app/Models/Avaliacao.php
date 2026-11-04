<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avaliacao extends Model
{
    protected $table = 'Avaliacao';

    const CREATED_AT = 'emitida_em';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_professor',
        'id_banca',
        'nota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
        ];
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_professor');
    }

    public function banca(): BelongsTo
    {
        return $this->belongsTo(Banca::class, 'id_banca');
    }
}
