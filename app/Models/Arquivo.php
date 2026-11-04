<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arquivo extends Model
{
    protected $table = 'Arquivo';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_usuario',
        'nome',
        'caminho',
        'sha256',
        'tamanho_bytes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tamanho_bytes' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TccLog::class, 'arquivo');
    }
}
