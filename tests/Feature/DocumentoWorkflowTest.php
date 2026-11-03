<?php

use App\Enums\AcaoDocumento;
use App\Enums\DocumentoStatus;
use App\Enums\DocumentoTipo;
use App\Enums\StatusAprovacao;
use App\Enums\TipoSolicitacao;
use App\Models\DocumentoEstagio;
use App\Models\Estagio;
use App\Models\Usuario;
use App\Services\DocumentoWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->fluxo = app(DocumentoWorkflow::class);
    $this->coordenacao = Usuario::factory()->coordenacao()->create();
    $this->estagio = Estagio::factory()->create(['horas_semanais' => 20, 'data_inicio' => '2026-02-01', 'data_termino' => '2026-07-31']);
    $this->aluno = $this->estagio->aluno;
    $this->orientador = $this->estagio->orientador;
    $this->documento = $this->fluxo->criar($this->aluno, [
        'id_estagio' => $this->estagio->id,
        'tipo' => DocumentoTipo::PlanoDeAtividades->value,
        'observacoes' => 'Entregas mensais',
    ]);
});

function acaoDoc(AcaoDocumento $acao, Usuario $autor, array $dados = []): DocumentoEstagio
{
    $doc = test()->documento->fresh();

    return test()->documento = test()->fluxo->executar($doc, $acao, $autor, $dados + ['revisao' => $doc->revisao]);
}

it('cria o documento preenchido com os dados do estágio, e a solicitação de documento', function () {
    $versao = $this->documento->versaoAtual;

    expect($this->documento->status)->toBe(DocumentoStatus::Rascunho)
        ->and($versao->numero)->toBe(1)
        ->and($versao->texto_preenchido)->toContain($this->aluno->nome_completo)
        ->toContain($this->estagio->empresa->nome)
        ->toContain('Período previsto: 01/02/2026 a 31/07/2026')
        ->toContain('Carga horária semanal: 20 horas')
        ->toContain('Entregas mensais')
        ->and($versao->modelo_hash)->toHaveLength(64)
        ->and($this->documento->solicitacao->tipo_de_solicitacao)->toBe(TipoSolicitacao::Documento)
        ->and($this->documento->solicitacao->status)->toBe(StatusAprovacao::EsperandoResposta);
});

it('percorre as aprovações: orientador, empresa (registrada pelo orientador) e coordenação', function () {
    acaoDoc(AcaoDocumento::Enviar, $this->aluno);
    acaoDoc(AcaoDocumento::Aprovar, $this->orientador);

    expect(fn () => acaoDoc(AcaoDocumento::Aprovar, $this->orientador))->toThrow(ValidationException::class);

    acaoDoc(AcaoDocumento::Aprovar, $this->orientador, ['confirmado' => '1', 'nota' => 'Supervisor aprovou por e-mail']);
    acaoDoc(AcaoDocumento::Aprovar, $this->coordenacao);

    $doc = $this->documento->fresh();
    expect($doc->status)->toBe(DocumentoStatus::Aprovado)
        ->and($doc->versaoAtual->status)->toBe(DocumentoStatus::Aprovado)
        ->and($doc->solicitacao->status)->toBe(StatusAprovacao::Aprovado)
        ->and($doc->solicitacao->resposta->usuario->is($this->coordenacao))->toBeTrue()
        ->and($doc->eventos()->pluck('acao')->all())->toBe([
            'Aprovação registrada: coordenação', 'Aprovação registrada: empresa',
            'Aprovação registrada: orientador', 'Encaminhar para análise', 'Documento criado',
        ]);
});

it('não deixa o orientador dar a aprovação da coordenação nem o aluno aprovar', function () {
    acaoDoc(AcaoDocumento::Enviar, $this->aluno);

    expect(fn () => acaoDoc(AcaoDocumento::Aprovar, $this->aluno))->toThrow(AuthorizationException::class);

    acaoDoc(AcaoDocumento::Aprovar, $this->orientador);
    acaoDoc(AcaoDocumento::Aprovar, $this->orientador, ['confirmado' => '1']);

    expect(fn () => acaoDoc(AcaoDocumento::Aprovar, $this->orientador))->toThrow(AuthorizationException::class);
});

it('devolução volta ao rascunho e exige motivo', function () {
    acaoDoc(AcaoDocumento::Enviar, $this->aluno);

    expect(fn () => acaoDoc(AcaoDocumento::Devolver, $this->orientador))->toThrow(ValidationException::class);

    acaoDoc(AcaoDocumento::Devolver, $this->orientador, ['nota' => 'Detalhar as entregas']);

    expect($this->documento->fresh()->status)->toBe(DocumentoStatus::Rascunho);
});

it('nova versão preserva a anterior, usa os dados atuais e reinicia as aprovações', function () {
    acaoDoc(AcaoDocumento::Enviar, $this->aluno);
    acaoDoc(AcaoDocumento::Aprovar, $this->orientador);
    $this->estagio->update(['supervisor' => 'Novo Supervisor']);

    acaoDoc(AcaoDocumento::Revisar, $this->aluno, ['observacoes' => 'Entregas quinzenais']);

    $versoes = $this->documento->versoes()->get();
    expect($versoes)->toHaveCount(2)
        ->and($versoes[0]->status)->toBe(DocumentoStatus::AnaliseEmpresa)
        ->and($versoes[0]->texto_preenchido)->not->toContain('Novo Supervisor')
        ->and($versoes[1]->status)->toBe(DocumentoStatus::Rascunho)
        ->and($versoes[1]->texto_preenchido)->toContain('Novo Supervisor')->toContain('Entregas quinzenais')
        ->and($this->documento->fresh()->status)->toBe(DocumentoStatus::Rascunho);
});

it('reabre a solicitação quando um documento aprovado ganha nova versão', function () {
    acaoDoc(AcaoDocumento::Enviar, $this->aluno);
    acaoDoc(AcaoDocumento::Aprovar, $this->coordenacao, ['confirmado' => '1', 'nota' => 'Parecer do orientador recebido']);
    acaoDoc(AcaoDocumento::Aprovar, $this->coordenacao, ['confirmado' => '1', 'nota' => 'Empresa aprovou']);
    acaoDoc(AcaoDocumento::Aprovar, $this->coordenacao);
    acaoDoc(AcaoDocumento::Revisar, $this->aluno);

    expect($this->documento->fresh()->solicitacao->status)->toBe(StatusAprovacao::EsperandoResposta);
});

it('só o aluno do estágio e a coordenação geram documentos', function () {
    $dados = ['id_estagio' => $this->estagio->id, 'tipo' => DocumentoTipo::RelatorioDeEstagio->value];

    expect($this->fluxo->criar($this->coordenacao, $dados)->eventos()->first()->nota)->toBe('Registrado pela coordenação em nome do aluno.')
        ->and(fn () => $this->fluxo->criar($this->orientador, $dados))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->fluxo->criar(Usuario::factory()->create(), $dados))->toThrow(AuthorizationException::class)
        ->and($this->aluno->can('create', [DocumentoEstagio::class, $this->estagio]))->toBeTrue()
        ->and($this->orientador->can('create', [DocumentoEstagio::class, $this->estagio]))->toBeFalse();
});

it('bloqueia a geração quando falta dado exigido pelo modelo', function () {
    $this->estagio->update(['supervisor' => null]);

    expect(fn () => $this->fluxo->criar($this->aluno, ['id_estagio' => $this->estagio->id, 'tipo' => DocumentoTipo::FichaDeAvaliacao->value]))
        ->toThrow(ValidationException::class, 'Complete o cadastro antes de gerar o documento: estagio.supervisor.');
});

it('mostra a prévia sem salvar nada', function () {
    $antes = DocumentoEstagio::count();

    $previa = $this->fluxo->previa($this->aluno, ['id_estagio' => $this->estagio->id, 'tipo' => DocumentoTipo::SolicitacaoDeEncerramento->value]);

    expect($previa['texto_preenchido'])->toContain($this->aluno->nome_completo)
        ->toContain('Não informado nesta requisição.')
        ->and(DocumentoEstagio::count())->toBe($antes);
});

it('preenche os quatro modelos sem deixar marcadores', function (DocumentoTipo $tipo) {
    $previa = $this->fluxo->previa($this->aluno, ['id_estagio' => $this->estagio->id, 'tipo' => $tipo->value]);

    expect($previa['texto_preenchido'])->not->toContain('{{');
})->with(DocumentoTipo::cases());
