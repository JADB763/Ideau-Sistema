<?php

namespace App\Models;

use App\Enums\DocumentoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Versão preservada de um documento: dados do estágio e texto preenchido na data de criação.
 */
class DocumentoVersao extends Model
{
    protected $table = 'DocumentoVersao';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_documento',
        'numero',
        'status',
        'snapshot',
        'observacoes',
        'modelo',
        'modelo_hash',
        'texto_preenchido',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'status' => DocumentoStatus::class,
            'snapshot' => 'array',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoEstagio::class, 'id_documento');
    }
}
