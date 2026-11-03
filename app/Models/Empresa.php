<?php

namespace App\Models;

use App\Models\Concerns\HasEventos;
use Database\Factories\EmpresaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    /** @use HasFactory<EmpresaFactory> */
    use HasEventos, HasFactory;

    protected $table = 'Empresa';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'cnpj',
        'email',
        'representante',
        'endereco',
    ];

    public function estagios(): HasMany
    {
        return $this->hasMany(Estagio::class, 'id_empresa');
    }
}
