<?php

namespace App\Services;

use App\Enums\AcaoDocumento;
use App\Enums\DocumentoStatus;
use App\Enums\DocumentoTipo;
use App\Enums\StatusAprovacao;
use App\Enums\TipoSolicitacao;
use App\Models\DocumentoEstagio;
use App\Models\Estagio;
use App\Models\RespostaSolicitacao;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Fluxo dos documentos de estágio (transition_document e record_save do Campus):
 * Rascunho -> orientador -> empresa -> coordenação -> Aprovado. O aluno gera, envia e cria
 * novas versões; o orientador aprova a sua etapa e registra a aprovação da empresa; a
 * coordenação dá a aprovação final e pode fazer qualquer etapa em nome do responsável.
 */
class DocumentoWorkflow
{
    public function __construct(private DocumentTemplates $modelos) {}

    /**
     * @return list<AcaoDocumento>
     */
    public function acoesDaEtapa(DocumentoStatus $status): array
    {
        return match ($status) {
            DocumentoStatus::Rascunho => [AcaoDocumento::Enviar, AcaoDocumento::Revisar],
            DocumentoStatus::AnaliseOrientador, DocumentoStatus::AnaliseEmpresa,
            DocumentoStatus::AnaliseCoordenacao => [AcaoDocumento::Aprovar, AcaoDocumento::Devolver, AcaoDocumento::Revisar],
            DocumentoStatus::Aprovado => [AcaoDocumento::Revisar],
        };
    }

    /**
     * @return list<AcaoDocumento>
     */
    public function acoesDisponiveis(DocumentoEstagio $documento, Usuario $usuario): array
    {
        return array_values(array_filter($this->acoesDaEtapa($documento->status), fn (AcaoDocumento $acao) => $this->pode($documento, $acao, $usuario)));
    }

    public function pode(DocumentoEstagio $documento, AcaoDocumento $acao, Usuario $usuario): bool
    {
        return $usuario->isCoordenacao() || $this->ehResponsavel($documento, $acao, $usuario);
    }

    public function emNomeDeOutro(DocumentoEstagio $documento, AcaoDocumento $acao, Usuario $usuario): bool
    {
        return $usuario->isCoordenacao() && ! $this->ehResponsavel($documento, $acao, $usuario);
    }

    /**
     * A aprovação da empresa é sempre registrada por outra pessoa (a empresa não tem acesso).
     */
    public function registraAprovacaoDaEmpresa(DocumentoEstagio $documento, AcaoDocumento $acao): bool
    {
        return $acao === AcaoDocumento::Aprovar && $documento->status === DocumentoStatus::AnaliseEmpresa;
    }

    /**
     * @return array<string, mixed>
     */
    public function regras(DocumentoEstagio $documento, AcaoDocumento $acao, Usuario $usuario): array
    {
        $emNomeDeOutro = $this->emNomeDeOutro($documento, $acao, $usuario);

        $regras = [
            'nota' => [$emNomeDeOutro || $acao === AcaoDocumento::Devolver ? 'required' : 'nullable', 'string', 'max:4000'],
        ];
        if ($acao === AcaoDocumento::Revisar) {
            $regras['observacoes'] = ['nullable', 'string', 'max:4000'];
        }
        if ($emNomeDeOutro || $this->registraAprovacaoDaEmpresa($documento, $acao)) {
            $regras['confirmado'] = ['accepted'];
        }

        return $regras;
    }

    /**
     * Prévia do documento preenchido, sem salvar (requisicao.php do Campus).
     *
     * @return array{modelo: string, modelo_hash: string, texto_preenchido: string}
     */
    public function previa(Usuario $autor, array $dados): array
    {
        [$estagio, $tipo, $observacoes] = $this->dadosDoPedido($autor, $dados);

        return $this->modelos->preencher($tipo, $this->modelos->snapshot($estagio), $observacoes);
    }

    /**
     * Cria a solicitação de documento, o documento e a versão 1 preenchida com os dados atuais.
     */
    public function criar(Usuario $autor, array $dados): DocumentoEstagio
    {
        [$estagio, $tipo, $observacoes] = $this->dadosDoPedido($autor, $dados);
        $snapshot = $this->modelos->snapshot($estagio);
        $preenchido = $this->modelos->preencher($tipo, $snapshot, $observacoes);

        return DB::transaction(function () use ($autor, $estagio, $tipo, $observacoes, $snapshot, $preenchido) {
            $solicitacao = Solicitacao::create([
                'tipo_de_solicitacao' => TipoSolicitacao::Documento,
                'id_aluno' => $estagio->id_aluno,
                'id_estagio' => $estagio->id,
                'status' => StatusAprovacao::EsperandoResposta,
                'descricao' => $tipo->value,
            ]);

            $documento = $estagio->documentos()->create([
                'id_solicitacao' => $solicitacao->id,
                'tipo' => $tipo,
                'status' => DocumentoStatus::Rascunho,
                'observacoes' => $observacoes,
                'revisao' => 1,
            ]);
            $documento->versoes()->create([
                'numero' => 1,
                'status' => DocumentoStatus::Rascunho,
                'snapshot' => $snapshot,
                'observacoes' => $observacoes,
                ...$preenchido,
            ]);
            $documento->registrarEvento($autor, 'Documento criado', $this->emNomeDoAluno($autor, $estagio));

            return $documento;
        });
    }

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function executar(DocumentoEstagio $documento, AcaoDocumento $acao, Usuario $autor, array $dados): DocumentoEstagio
    {
        return DB::transaction(function () use ($documento, $acao, $autor, $dados) {
            $documento = DocumentoEstagio::lockForUpdate()->findOrFail($documento->getKey());

            if (! in_array($acao, $this->acoesDaEtapa($documento->status), true)) {
                $this->falha('Ação indisponível na etapa atual.');
            }
            if (! $this->pode($documento, $acao, $autor)) {
                throw new AuthorizationException('Esta etapa cabe a outra pessoa.');
            }
            if ((int) ($dados['revisao'] ?? 0) !== $documento->revisao) {
                $this->falha('Este documento mudou em outra aba. Atualize a página antes de continuar.');
            }
            $validos = Validator::make($dados, $this->regras($documento, $acao, $autor))->validate();
            $nota = $validos['nota'] ?? null;
            $emNomeDeOutro = $this->emNomeDeOutro($documento, $acao, $autor);
            $rotulo = $acao === AcaoDocumento::Aprovar ? 'Aprovação registrada: '.$this->etapa($documento->status) : $acao->label();

            match ($acao) {
                AcaoDocumento::Enviar => $documento->status = DocumentoStatus::AnaliseOrientador,
                AcaoDocumento::Aprovar => $this->aprovar($documento, $autor, $nota),
                AcaoDocumento::Devolver => $documento->status = DocumentoStatus::Rascunho,
                AcaoDocumento::Revisar => $this->revisar($documento, $validos['observacoes'] ?? null),
            };

            // A versão mais recente acompanha a etapa do documento; as anteriores ficam como estavam.
            $documento->versaoAtual()->first()->update(['status' => $documento->status]);
            $documento->revisao++;
            $documento->save();

            $prefixo = $emNomeDeOutro ? 'Registrado pela coordenação em nome do responsável. ' : '';
            $documento->registrarEvento($autor, $rotulo, trim($prefixo.$nota));

            return $documento;
        });
    }

    private function ehResponsavel(DocumentoEstagio $documento, AcaoDocumento $acao, Usuario $usuario): bool
    {
        $estagio = $documento->estagio;
        $ehAluno = $usuario->isAluno() && $usuario->id === $estagio->id_aluno;
        $ehOrientador = $usuario->isProfessor() && $usuario->id === $estagio->id_professor_orientador;

        return match ($acao) {
            AcaoDocumento::Enviar, AcaoDocumento::Revisar => $ehAluno,
            AcaoDocumento::Aprovar, AcaoDocumento::Devolver => $documento->status === DocumentoStatus::AnaliseCoordenacao
                ? $usuario->isCoordenacao()
                : $ehOrientador,
        };
    }

    private function aprovar(DocumentoEstagio $documento, Usuario $autor, ?string $nota): void
    {
        $etapas = DocumentoStatus::cases();
        $documento->status = $etapas[array_search($documento->status, $etapas, true) + 1];

        if ($documento->status === DocumentoStatus::Aprovado && $documento->solicitacao) {
            $resposta = RespostaSolicitacao::create([
                'id_solicitacao' => $documento->id_solicitacao,
                'id_usuario' => $autor->id,
                'parecer' => $nota,
            ]);
            $documento->solicitacao->update(['id_resposta' => $resposta->id, 'status' => StatusAprovacao::Aprovado]);
        }
    }

    /**
     * Nova versão com os dados e o modelo atuais; a anterior é preservada e as aprovações recomeçam.
     */
    private function revisar(DocumentoEstagio $documento, ?string $observacoes): void
    {
        $snapshot = $this->modelos->snapshot($documento->estagio);

        $documento->versoes()->create([
            'numero' => $documento->versoes()->max('numero') + 1,
            'status' => DocumentoStatus::Rascunho,
            'snapshot' => $snapshot,
            'observacoes' => $observacoes,
            ...$this->modelos->preencher($documento->tipo, $snapshot, $observacoes),
        ]);

        $documento->observacoes = $observacoes;
        $documento->status = DocumentoStatus::Rascunho;
        $documento->solicitacao?->update(['status' => StatusAprovacao::EsperandoResposta]);
    }

    /**
     * Valida o pedido de documento. O aluno pede para os próprios estágios; a coordenação, para qualquer um.
     *
     * @return array{0: Estagio, 1: DocumentoTipo, 2: ?string}
     */
    private function dadosDoPedido(Usuario $autor, array $dados): array
    {
        $validos = Validator::make($dados, [
            'id_estagio' => ['required', 'integer', Rule::exists('Estagio', 'id')],
            'tipo' => ['required', Rule::enum(DocumentoTipo::class)],
            'observacoes' => ['nullable', 'string', 'max:4000'],
        ])->validate();

        $estagio = Estagio::findOrFail($validos['id_estagio']);
        if (! $autor->isCoordenacao() && ! ($autor->isAluno() && $autor->id === $estagio->id_aluno)) {
            throw new AuthorizationException('Somente o aluno do estágio e a coordenação geram documentos.');
        }

        return [$estagio, DocumentoTipo::from($validos['tipo']), $validos['observacoes'] ?? null];
    }

    private function emNomeDoAluno(Usuario $autor, Estagio $estagio): string
    {
        return $autor->id === $estagio->id_aluno ? '' : 'Registrado pela coordenação em nome do aluno.';
    }

    private function etapa(DocumentoStatus $status): string
    {
        return match ($status) {
            DocumentoStatus::AnaliseOrientador => 'orientador',
            DocumentoStatus::AnaliseEmpresa => 'empresa',
            default => 'coordenação',
        };
    }

    private function falha(string $mensagem): never
    {
        throw ValidationException::withMessages(['acao' => $mensagem]);
    }
}
