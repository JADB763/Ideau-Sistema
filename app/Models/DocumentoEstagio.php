<?php

namespace App\Models;

use App\Enums\DocumentoStatus;
use App\Enums\DocumentoTipo;
use App\Models\Concerns\HasEventos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentoEstagio extends Model
{
    use HasEventos;

    protected $table = 'DocumentoEstagio';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_estagio',
        'id_solicitacao',
        'tipo',
        'status',
        'observacoes',
        'revisao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => DocumentoTipo::class,
            'status' => DocumentoStatus::class,
            'revisao' => 'integer',
        ];
    }

    public function estagio(): BelongsTo
    {
        return $this->belongsTo(Estagio::class, 'id_estagio');
    }

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class, 'id_solicitacao');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(DocumentoVersao::class, 'id_documento')->orderBy('numero');
    }

    public function versaoAtual(): HasOne
    {
        return $this->hasOne(DocumentoVersao::class, 'id_documento')->latestOfMany('numero');
    }
}
