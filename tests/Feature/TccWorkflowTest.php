<?php

use App\Enums\AcaoTcc;
use App\Enums\FuncaoBanca;
use App\Enums\StatusAprovacao;
use App\Enums\TccStatus;
use App\Enums\TipoSolicitacao;
use App\Models\Tcc;
use App\Models\Usuario;
use App\Services\TccWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('local');
    $this->fluxo = app(TccWorkflow::class);
    $this->coordenacao = Usuario::factory()->coordenacao()->create();
    $this->aluno = Usuario::factory()->create();
    $this->orientador = Usuario::factory()->professor()->create();
    [$this->avaliador1, $this->avaliador2] = Usuario::factory()->professor()->count(2)->create()->all();
    $this->tcc = $this->fluxo->criarProposta($this->aluno, [
        'id_professor_orientador' => $this->orientador->id,
        'titulo' => 'Sistema de estágios',
        'resumo' => 'Proposta de teste',
    ]);
});

function pdf(string $nome = 'tcc.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nome, "%PDF-1.4\n1 0 obj << >> endobj\n%%EOF\n");
}

/**
 * Executa a ação com a revisão atual do TCC.
 */
function acao(AcaoTcc $acao, Usuario $autor, array $dados = []): Tcc
{
    $tcc = test()->tcc->fresh();

    return test()->tcc = test()->fluxo->executar($tcc, $acao, $autor, $dados + ['revisao' => $tcc->revisao]);
}

/**
 * Leva o TCC até a etapa indicada, cada passo feito pelo responsável.
 */
function avancarAte(TccStatus $etapa): void
{
    $t = test();
    $passos = [
        [AcaoTcc::Solicitar, $t->aluno, []],
        [AcaoTcc::Aceitar, $t->orientador, []],
        [AcaoTcc::AprovarOrientacao, $t->coordenacao, []],
        [AcaoTcc::Versao, $t->aluno, ['titulo' => 'Título final', 'pdf' => pdf()]],
        [AcaoTcc::Liberar, $t->orientador, []],
        [AcaoTcc::SolicitarDefesa, $t->aluno, []],
        [AcaoTcc::AutorizarDefesa, $t->coordenacao, []],
        [AcaoTcc::Agendar, $t->coordenacao, ['data' => '2026-12-10', 'hora' => '14:00', 'local' => 'Sala 3', 'avaliador1' => $t->avaliador1->id, 'avaliador2' => $t->avaliador2->id]],
    ];

    foreach ($passos as [$passo, $autor, $dados]) {
        if ($t->tcc->fresh()->status === $etapa) {
            return;
        }
        acao($passo, $autor, $dados);
    }
}

function darNotas(float $orientador, float $a1, float $a2): void
{
    acao(AcaoTcc::Avaliar, test()->orientador, ['nota_banca' => $orientador]);
    acao(AcaoTcc::Avaliar, test()->avaliador1, ['nota_banca' => $a1]);
    acao(AcaoTcc::Avaliar, test()->avaliador2, ['nota_banca' => $a2]);
}

it('percorre o fluxo completo com cada etapa feita pelo responsável', function () {
    avancarAte(TccStatus::DefesaAgendada);
    darNotas(7.5, 8.25, 6);
    acao(AcaoTcc::RegistrarAta, $this->coordenacao, ['nota_minima' => 7]);
    acao(AcaoTcc::Concluir, $this->coordenacao);

    $tcc = $this->tcc->fresh();
    $ata = $tcc->defesaAtual->banca->ata;

    expect($tcc->status)->toBe(TccStatus::Concluido)
        ->and($tcc->aprovado)->toBe(StatusAprovacao::Aprovado)
        ->and($tcc->titulo)->toBe('Título final')
        ->and($ata->media_final)->toBe('7.25')
        ->and($ata->aprovado)->toBeTrue()
        ->and($ata->homologada_em)->not->toBeNull()
        ->and($ata->avaliacao1->id_professor)->toBe($this->orientador->id)
        ->and($tcc->eventos()->count())->toBe(14);
});

it('registra as solicitações de orientação e defesa com as respostas', function () {
    avancarAte(TccStatus::DefesaAutorizada);

    $orientacao = $this->tcc->solicitacoes()->where('tipo_de_solicitacao', TipoSolicitacao::Orientador)->first();
    $defesa = $this->tcc->solicitacoes()->where('tipo_de_solicitacao', TipoSolicitacao::Defesa)->first();

    expect($orientacao->status)->toBe(StatusAprovacao::Aprovado)
        ->and($orientacao->resposta->usuario->is($this->coordenacao))->toBeTrue()
        ->and($defesa->status)->toBe(StatusAprovacao::Aprovado)
        ->and($defesa->defesa->id_tcc)->toBe($this->tcc->id);
});

it('reprova quando a média fica abaixo da mínima', function () {
    avancarAte(TccStatus::DefesaAgendada);
    darNotas(5, 6, 6.5);
    acao(AcaoTcc::RegistrarAta, $this->coordenacao, ['nota_minima' => 7]);
    acao(AcaoTcc::Concluir, $this->coordenacao);

    expect($this->tcc->fresh()->aprovado)->toBe(StatusAprovacao::NaoAprovado)
        ->and($this->tcc->defesaAtual->banca->ata->media_final)->toBe('5.83');
});

it('não deixa outra pessoa fazer a etapa do responsável', function (string $quem, AcaoTcc $passo) {
    avancarAte(match ($passo) {
        AcaoTcc::Solicitar => TccStatus::Proposta,
        AcaoTcc::Aceitar => TccStatus::AguardandoOrientador,
        AcaoTcc::AprovarOrientacao => TccStatus::AguardandoCoordenacao,
    });

    expect(fn () => acao($passo, $this->{$quem}))->toThrow(AuthorizationException::class);
})->with([
    'orientador solicitando no lugar do aluno' => ['orientador', AcaoTcc::Solicitar],
    'aluno aceitando a própria orientação' => ['aluno', AcaoTcc::Aceitar],
    'outro professor aceitando' => ['avaliador1', AcaoTcc::Aceitar],
    'orientador aprovando no lugar da coordenação' => ['orientador', AcaoTcc::AprovarOrientacao],
]);

it('lista só as ações que o usuário pode fazer na etapa', function () {
    avancarAte(TccStatus::AguardandoOrientador);
    $tcc = $this->tcc->fresh();

    expect($this->fluxo->acoesDisponiveis($tcc, $this->orientador))->toBe([AcaoTcc::Aceitar, AcaoTcc::Recusar])
        ->and($this->fluxo->acoesDisponiveis($tcc, $this->aluno))->toBe([])
        ->and($this->fluxo->acoesDisponiveis($tcc, $this->coordenacao))->toBe([AcaoTcc::Aceitar, AcaoTcc::Recusar]);
});

it('deixa a coordenação agir em nome do responsável, com confirmação e justificativa', function () {
    avancarAte(TccStatus::AguardandoOrientador);

    expect(fn () => acao(AcaoTcc::Aceitar, $this->coordenacao, ['nota' => 'Aceite por e-mail']))
        ->toThrow(ValidationException::class)
        ->and(fn () => acao(AcaoTcc::Aceitar, $this->coordenacao, ['confirmado' => '1']))
        ->toThrow(ValidationException::class);

    acao(AcaoTcc::Aceitar, $this->coordenacao, ['confirmado' => '1', 'nota' => 'Aceite por e-mail em 10/03']);

    expect($this->tcc->fresh()->status)->toBe(TccStatus::AguardandoCoordenacao)
        ->and($this->tcc->eventos()->first()->nota)->toBe('Registrado pela coordenação em nome do responsável. Aceite por e-mail em 10/03');
});

it('devolve para a etapa apropriada, exigindo o motivo', function (TccStatus $de, string $quem, TccStatus $para) {
    avancarAte($de);

    expect(fn () => acao(AcaoTcc::Recusar, $this->{$quem}))->toThrow(ValidationException::class);

    acao(AcaoTcc::Recusar, $this->{$quem}, ['nota' => 'Ajustar o escopo']);

    expect($this->tcc->fresh()->status)->toBe($para);
})->with([
    'orientador recusa a orientação' => [TccStatus::AguardandoOrientador, 'orientador', TccStatus::Proposta],
    'coordenação recusa a orientação' => [TccStatus::AguardandoCoordenacao, 'coordenacao', TccStatus::Proposta],
    'orientador devolve a versão' => [TccStatus::VersaoEmAnalise, 'orientador', TccStatus::EmOrientacao],
    'coordenação recusa a defesa' => [TccStatus::DefesaSolicitada, 'coordenacao', TccStatus::LiberadoParaDefesa],
]);

it('marca a solicitação como não aprovada na devolução', function () {
    avancarAte(TccStatus::AguardandoOrientador);
    acao(AcaoTcc::Recusar, $this->orientador, ['nota' => 'Sem disponibilidade']);

    $solicitacao = $this->tcc->solicitacoes()->first();
    expect($solicitacao->status)->toBe(StatusAprovacao::NaoAprovado)
        ->and($solicitacao->resposta->parecer)->toBe('Sem disponibilidade');
});

it('registra reuniões de orientação', function () {
    avancarAte(TccStatus::EmOrientacao);
    acao(AcaoTcc::Reuniao, $this->orientador, ['data' => '2026-03-15', 'nota' => 'Revisão do capítulo 1']);

    expect($this->tcc->orientacoes()->count())->toBe(1)
        ->and($this->tcc->orientacoes()->first()->comentario)->toBe('Revisão do capítulo 1')
        ->and($this->tcc->fresh()->status)->toBe(TccStatus::EmOrientacao);
});

it('guarda cada versão em PDF e preserva as anteriores', function () {
    avancarAte(TccStatus::VersaoEmAnalise);
    acao(AcaoTcc::Recusar, $this->orientador, ['nota' => 'Revisar metodologia']);
    acao(AcaoTcc::Versao, $this->aluno, ['titulo' => 'Título revisado', 'pdf' => pdf('v2.pdf')]);

    $versoes = $this->tcc->versoes()->with('anexo')->get();

    expect($versoes)->toHaveCount(2)
        ->and($versoes[1]->anexo->nome)->toBe('v2.pdf')
        ->and($this->tcc->fresh()->titulo)->toBe('Título revisado');
    Storage::disk('local')->assertExists($versoes[0]->anexo->caminho);
    Storage::disk('local')->assertExists($versoes[1]->anexo->caminho);
});

it('recusa arquivo que não é PDF e não deixa arquivo para trás', function () {
    avancarAte(TccStatus::EmOrientacao);
    $falso = UploadedFile::fake()->createWithContent('tcc.pdf', 'isto não é um pdf');

    expect(fn () => acao(AcaoTcc::Versao, $this->aluno, ['titulo' => 'X', 'pdf' => $falso]))->toThrow(ValidationException::class)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and($this->tcc->fresh()->status)->toBe(TccStatus::EmOrientacao);
});

it('exige liberação de versão antes de solicitar defesa', function () {
    avancarAte(TccStatus::VersaoEmAnalise);

    expect(fn () => acao(AcaoTcc::SolicitarDefesa, $this->aluno))->toThrow(ValidationException::class, 'Ação indisponível na etapa atual.');
});

it('exige três professores distintos na banca', function (string $a1, string $a2) {
    avancarAte(TccStatus::DefesaAutorizada);

    expect(fn () => acao(AcaoTcc::Agendar, $this->coordenacao, [
        'data' => '2026-12-10', 'hora' => '14:00', 'local' => 'Sala 3',
        'avaliador1' => $this->{$a1}->id, 'avaliador2' => $this->{$a2}->id,
    ]))->toThrow(ValidationException::class, 'A banca deve ter três professores distintos, incluindo o orientador.');
})->with([
    'avaliadores repetidos' => ['avaliador1', 'avaliador1'],
    'orientador como avaliador' => ['orientador', 'avaliador2'],
]);

it('monta a banca com o orientador como presidente', function () {
    avancarAte(TccStatus::DefesaAgendada);
    $banca = $this->tcc->defesaAtual->banca;

    expect($banca->professores->firstWhere('id', $this->orientador->id)->pivot->funcao)->toBe(FuncaoBanca::Presidente)
        ->and($banca->professores)->toHaveCount(3)
        ->and($this->tcc->defesaAtual->agendado_para->format('d/m/Y H:i'))->toBe('10/12/2026 14:00');
});

it('só professores da banca dão nota, entre 0 e 10, e podem corrigi-la antes da ata', function () {
    avancarAte(TccStatus::DefesaAgendada);
    $deFora = Usuario::factory()->professor()->create();

    expect(fn () => acao(AcaoTcc::Avaliar, $deFora, ['nota_banca' => 8]))->toThrow(AuthorizationException::class)
        ->and(fn () => acao(AcaoTcc::Avaliar, $this->avaliador1, ['nota_banca' => 11]))->toThrow(ValidationException::class);

    acao(AcaoTcc::Avaliar, $this->avaliador1, ['nota_banca' => 6]);
    acao(AcaoTcc::Avaliar, $this->avaliador1, ['nota_banca' => 9.5]);

    expect($this->tcc->defesaAtual->banca->avaliacoes()->sole()->nota)->toBe('9.50');
});

it('a coordenação registra a nota de um professor da banca', function () {
    avancarAte(TccStatus::DefesaAgendada);

    acao(AcaoTcc::Avaliar, $this->coordenacao, ['nota_banca' => 8, 'id_professor' => $this->avaliador2->id, 'confirmado' => '1', 'nota' => 'Ficha entregue em papel']);

    expect($this->tcc->defesaAtual->banca->avaliacoes()->sole()->id_professor)->toBe($this->avaliador2->id);
});

it('só registra a ata com as três notas e trava as notas depois', function () {
    avancarAte(TccStatus::DefesaAgendada);
    acao(AcaoTcc::Avaliar, $this->orientador, ['nota_banca' => 8]);

    expect(fn () => acao(AcaoTcc::RegistrarAta, $this->coordenacao, ['nota_minima' => 7]))
        ->toThrow(ValidationException::class, 'Aguardando as notas dos três professores da banca.');

    acao(AcaoTcc::Avaliar, $this->avaliador1, ['nota_banca' => 8]);
    acao(AcaoTcc::Avaliar, $this->avaliador2, ['nota_banca' => 8]);
    acao(AcaoTcc::RegistrarAta, $this->coordenacao, ['nota_minima' => 7]);

    expect(fn () => acao(AcaoTcc::Avaliar, $this->avaliador1, ['nota_banca' => 2]))
        ->toThrow(ValidationException::class, 'Ação indisponível na etapa atual.');
});

it('recusa ação de uma tela desatualizada', function () {
    $antigo = $this->tcc->revisao;
    acao(AcaoTcc::Solicitar, $this->aluno);

    expect(fn () => $this->fluxo->executar($this->tcc, AcaoTcc::Aceitar, $this->orientador, ['revisao' => $antigo]))
        ->toThrow(ValidationException::class, 'Este TCC mudou em outra aba.');
});

it('edita a proposta só antes de solicitar a orientação', function () {
    $this->fluxo->atualizarProposta($this->tcc, $this->aluno, [
        'revisao' => $this->tcc->revisao, 'id_professor_orientador' => $this->orientador->id,
        'titulo' => 'Novo tema', 'resumo' => 'Novo resumo',
    ]);
    expect($this->tcc->fresh()->titulo)->toBe('Novo tema');

    acao(AcaoTcc::Solicitar, $this->aluno);

    expect(fn () => $this->fluxo->atualizarProposta($this->tcc->fresh(), $this->aluno, [
        'revisao' => $this->tcc->fresh()->revisao, 'id_professor_orientador' => $this->orientador->id,
        'titulo' => 'Outro', 'resumo' => 'Outro',
    ]))->toThrow(ValidationException::class);
});

it('cria propostas: aluno para si, coordenação para qualquer aluno, professor não', function () {
    $dados = ['id_professor_orientador' => $this->orientador->id, 'titulo' => 'Tema', 'resumo' => 'Resumo'];

    $daCoordenacao = $this->fluxo->criarProposta($this->coordenacao, $dados + ['id_aluno' => $this->aluno->id]);

    expect($daCoordenacao->id_aluno)->toBe($this->aluno->id)
        ->and(fn () => $this->fluxo->criarProposta($this->orientador, $dados))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->fluxo->criarProposta($this->aluno, ['id_professor_orientador' => $this->aluno->id] + $dados))->toThrow(ValidationException::class);
});
