<?php

use App\Enums\DocumentoTipo;
use App\Enums\FuncaoBanca;
use App\Enums\StatusAprovacao;
use App\Enums\TipoSolicitacao;
use App\Models\DocumentoEstagio;
use App\Models\Empresa;
use App\Models\Estagio;
use App\Models\Solicitacao;
use App\Models\Tcc;
use App\Models\Usuario;

beforeEach(function () {
    $this->coordenacao = Usuario::factory()->coordenacao()->create();
    $this->outroAluno = Usuario::factory()->create();
    $this->outroProfessor = Usuario::factory()->professor()->create();
});

it('mostra o estágio ao aluno, ao orientador e à coordenação', function () {
    $estagio = Estagio::factory()->create();
    $outro = Estagio::factory()->create();

    expect(Estagio::visivelPara($estagio->aluno)->pluck('id')->all())->toBe([$estagio->id])
        ->and(Estagio::visivelPara($estagio->orientador)->pluck('id')->all())->toBe([$estagio->id])
        ->and(Estagio::visivelPara($this->coordenacao)->count())->toBe(2)
        ->and(Estagio::visivelPara($this->outroAluno)->count())->toBe(0)
        ->and($estagio->aluno->can('view', $estagio))->toBeTrue()
        ->and($estagio->aluno->can('view', $outro))->toBeFalse()
        ->and($this->outroProfessor->can('view', $estagio))->toBeFalse();
});

it('só a coordenação cria e altera estágios', function () {
    $estagio = Estagio::factory()->create();

    expect($this->coordenacao->can('create', Estagio::class))->toBeTrue()
        ->and($estagio->aluno->can('create', Estagio::class))->toBeFalse()
        ->and($estagio->orientador->can('update', $estagio))->toBeFalse();
});

it('mostra o TCC aos professores da banca', function () {
    $tcc = Tcc::factory()->create();
    $membro = Usuario::factory()->professor()->create();
    $banca = $tcc->defesas()->create(['id_aluno' => $tcc->id_aluno])->banca()->create();
    $banca->professores()->attach($membro->id, ['funcao' => FuncaoBanca::Avaliador->value]);

    expect($membro->can('view', $tcc))->toBeTrue()
        ->and($tcc->orientador->can('view', $tcc))->toBeTrue()
        ->and($tcc->aluno->can('view', $tcc))->toBeTrue()
        ->and($this->coordenacao->can('view', $tcc))->toBeTrue()
        ->and($this->outroProfessor->can('view', $tcc))->toBeFalse()
        ->and($this->outroAluno->can('view', $tcc))->toBeFalse()
        ->and(Tcc::visivelPara($membro)->pluck('id')->all())->toBe([$tcc->id]);
});

it('mostra o documento a quem vê o estágio', function () {
    $documento = Estagio::factory()->create()->documentos()->create(['tipo' => DocumentoTipo::PlanoDeAtividades]);
    $estagio = $documento->estagio;

    expect($estagio->aluno->can('view', $documento))->toBeTrue()
        ->and($estagio->orientador->can('view', $documento))->toBeTrue()
        ->and($this->outroAluno->can('view', $documento))->toBeFalse()
        ->and(DocumentoEstagio::visivelPara($this->outroProfessor)->count())->toBe(0);
});

it('mostra a solicitação ao aluno e ao orientador do TCC', function () {
    $tcc = Tcc::factory()->create();
    $solicitacao = Solicitacao::create([
        'tipo_de_solicitacao' => TipoSolicitacao::Orientador,
        'id_aluno' => $tcc->id_aluno,
        'id_tcc' => $tcc->id,
        'status' => StatusAprovacao::EsperandoResposta,
    ]);

    expect($tcc->aluno->can('view', $solicitacao))->toBeTrue()
        ->and($tcc->orientador->can('view', $solicitacao))->toBeTrue()
        ->and($this->coordenacao->can('view', $solicitacao))->toBeTrue()
        ->and($this->outroProfessor->can('view', $solicitacao))->toBeFalse()
        ->and($this->outroAluno->can('view', $solicitacao))->toBeFalse();
});

it('só a coordenação gerencia contas; cada um vê a própria', function () {
    expect($this->coordenacao->can('viewAny', Usuario::class))->toBeTrue()
        ->and($this->coordenacao->can('create', Usuario::class))->toBeTrue()
        ->and($this->outroAluno->can('viewAny', Usuario::class))->toBeFalse()
        ->and($this->outroAluno->can('create', Usuario::class))->toBeFalse()
        ->and($this->outroAluno->can('view', $this->outroAluno))->toBeTrue()
        ->and($this->outroAluno->can('view', $this->outroProfessor))->toBeFalse()
        ->and($this->outroProfessor->can('update', $this->outroProfessor))->toBeFalse();
});

it('todos veem empresas; só a coordenação cadastra', function () {
    $empresa = Empresa::factory()->create();

    expect($this->outroAluno->can('view', $empresa))->toBeTrue()
        ->and($this->outroAluno->can('create', Empresa::class))->toBeFalse()
        ->and($this->coordenacao->can('update', $empresa))->toBeTrue();
});
