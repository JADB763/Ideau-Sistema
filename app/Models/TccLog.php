<?php

namespace App\Models;

use App\Enums\TipoLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TccLog extends Model
{
    protected $table = 'TCCLog';

    const CREATED_AT = 'data_enviado';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_tcc',
        'tipo',
        'data_orientacao',
        'id_usuario',
        'arquivo',
        'titulo',
        'comentario',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoLog::class,
            'data_orientacao' => 'date',
        ];
    }

    public function tcc(): BelongsTo
    {
        return $this->belongsTo(Tcc::class, 'id_tcc');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    /**
     * PDF da versão. A coluna se chama arquivo, por isso a relação tem outro nome.
     */
    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Arquivo::class, 'arquivo');
    }
}
