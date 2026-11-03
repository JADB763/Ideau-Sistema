<?php

namespace App\Models;

use App\Enums\TipoUsuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable;

    protected $table = 'Usuario';

    public $timestamps = false;

    /**
     * A senha fica na coluna senha.
     */
    protected $authPasswordName = 'senha';

    /**
     * A tabela não tem coluna de "lembrar de mim".
     */
    protected $rememberTokenName = '';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nome_completo',
        'data_nascimento',
        'cpf',
        'email',
        'senha',
        'tipo_usuario',
        'matricula',
        'curso',
        'instituicao',
        'area',
        'ativo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'senha',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'senha' => 'hashed',
            'tipo_usuario' => TipoUsuario::class,
            'ativo' => 'boolean',
        ];
    }

    public function isAluno(): bool
    {
        return $this->tipo_usuario === TipoUsuario::Aluno;
    }

    public function isProfessor(): bool
    {
        return $this->tipo_usuario === TipoUsuario::Professor;
    }

    public function isCoordenacao(): bool
    {
        return $this->tipo_usuario === TipoUsuario::Coordenacao;
    }

    public function scopeAlunos(Builder $query): void
    {
        $query->where('tipo_usuario', TipoUsuario::Aluno);
    }

    public function scopeProfessores(Builder $query): void
    {
        $query->where('tipo_usuario', TipoUsuario::Professor);
    }

    public function scopeCoordenacao(Builder $query): void
    {
        $query->where('tipo_usuario', TipoUsuario::Coordenacao);
    }

    public function scopeAtivos(Builder $query): void
    {
        $query->where('ativo', true);
    }

    /**
     * Estágios em que o usuário é o aluno.
     */
    public function estagios(): HasMany
    {
        return $this->hasMany(Estagio::class, 'id_aluno');
    }

    /**
     * Estágios em que o usuário é o professor orientador.
     */
    public function estagiosOrientados(): HasMany
    {
        return $this->hasMany(Estagio::class, 'id_professor_orientador');
    }

    /**
     * TCCs em que o usuário é o aluno.
     */
    public function tccs(): HasMany
    {
        return $this->hasMany(Tcc::class, 'id_aluno');
    }

    /**
     * TCCs em que o usuário é o professor orientador.
     */
    public function tccsOrientados(): HasMany
    {
        return $this->hasMany(Tcc::class, 'id_professor_orientador');
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(Solicitacao::class, 'id_aluno');
    }

    public function participacoesBanca(): HasMany
    {
        return $this->hasMany(ParticipanteBanca::class, 'id_professor');
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class, 'id_professor');
    }
}
