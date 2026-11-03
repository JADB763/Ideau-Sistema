<?php

namespace App\Models;

use App\Enums\EstagioStatus;
use App\Models\Concerns\HasEventos;
use Database\Factories\EstagioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estagio extends Model
{
    /** @use HasFactory<EstagioFactory> */
    use HasEventos, HasFactory;

    protected $table = 'Estagio';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_empresa',
        'id_aluno',
        'id_professor_orientador',
        'data_inicio',
        'data_termino',
        'status',
        'supervisor',
        'supervisor_email',
        'horas_semanais',
        'atividades',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_termino' => 'date',
            'status' => EstagioStatus::class,
            'horas_semanais' => 'decimal:1',
        ];
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_aluno');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function orientador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_professor_orientador');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoEstagio::class, 'id_estagio');
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(Solicitacao::class, 'id_estagio');
    }
}
