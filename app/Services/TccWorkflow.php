<?php

namespace App\Services;

use App\Enums\AcaoTcc;
use App\Enums\FuncaoBanca;
use App\Enums\StatusAprovacao;
use App\Enums\TccStatus;
use App\Enums\TipoLog;
use App\Enums\TipoSolicitacao;
use App\Enums\TipoUsuario;
use App\Models\Arquivo;
use App\Models\Avaliacao;
use App\Models\Banca;
use App\Models\RespostaSolicitacao;
use App\Models\Solicitacao;
use App\Models\Tcc;
use App\Models\Usuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Fluxo do TCC (transition_tcc e record_action do Campus), com cada etapa feita por quem é responsável:
 * aluno propõe, envia versões e solicita; orientador aceita, orienta e libera; banca dá as notas;
 * coordenação aprova, autoriza, agenda e homologa. A coordenação pode fazer qualquer etapa em nome
 * do responsável, confirmando que registra uma decisão recebida.
 */
class TccWorkflow
{
    /**
     * Ações oferecidas em cada etapa (available_actions do Campus).
     *
     * @return list<AcaoTcc>
     */
    public function acoesDaEtapa(TccStatus $status): array
    {
        return match ($status) {
            TccStatus::Proposta => [AcaoTcc::Solicitar],
            TccStatus::AguardandoOrientador => [AcaoTcc::Aceitar, AcaoTcc::Recusar],
            TccStatus::AguardandoCoordenacao => [AcaoTcc::AprovarOrientacao, AcaoTcc::Recusar],
            TccStatus::EmOrientacao => [AcaoTcc::Reuniao, AcaoTcc::Versao],
            TccStatus::VersaoEmAnalise => [AcaoTcc::Liberar, AcaoTcc::Recusar],
            TccStatus::LiberadoParaDefesa => [AcaoTcc::SolicitarDefesa],
            TccStatus::DefesaSolicitada => [AcaoTcc::AutorizarDefesa, AcaoTcc::Recusar],
            TccStatus::DefesaAutorizada => [AcaoTcc::Agendar],
            TccStatus::DefesaAgendada => [AcaoTcc::Avaliar, AcaoTcc::RegistrarAta],
            TccStatus::Avaliado => [AcaoTcc::Concluir],
            TccStatus::Concluido => [],
        };
    }

    /**
     * Ações que o usuário pode fazer agora neste TCC.
     *
     * @return list<AcaoTcc>
     */
    public function acoesDisponiveis(Tcc $tcc, Usuario $usuario): array
    {
        return array_values(array_filter($this->acoesDaEtapa($tcc->status), fn (AcaoTcc $acao) => $this->pode($tcc, $acao, $usuario)));
    }

    public function pode(Tcc $tcc, AcaoTcc $acao, Usuario $usuario): bool
    {
        return $usuario->isCoordenacao() || $this->ehResponsavel($tcc, $acao, $usuario);
    }

    /**
     * A coordenação está registrando uma etapa que cabe a outra pessoa.
     */
    public function emNomeDeOutro(Tcc $tcc, AcaoTcc $acao, Usuario $usuario): bool
    {
        return $usuario->isCoordenacao() && ! $this->ehResponsavel($tcc, $acao, $usuario);
    }

    /**
     * Regras de validação dos dados de cada ação (também usadas para montar os formulários).
     *
     * @return array<string, mixed>
     */
    public function regras(Tcc $tcc, AcaoTcc $acao, Usuario $usuario): array
    {
        $emNomeDeOutro = $this->emNomeDeOutro($tcc, $acao, $usuario);
        $notaObrigatoria = $emNomeDeOutro || in_array($acao, [AcaoTcc::Recusar, AcaoTcc::Reuniao], true);
        $professorAtivo = Rule::exists('Usuario', 'id')->where('tipo_usuario', TipoUsuario::Professor->value)->where('ativo', true);

        $regras = match ($acao) {
            AcaoTcc::Reuniao => ['data' => ['required', 'date', 'before_or_equal:today']],
            AcaoTcc::Versao => [
                'titulo' => ['required', 'string', 'max:200'],
                'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            ],
            AcaoTcc::Agendar => [
                'data' => ['required', 'date_format:Y-m-d'],
                'hora' => ['required', 'date_format:H:i'],
                'local' => ['required', 'string', 'max:1000'],
                'avaliador1' => ['required', 'integer', $professorAtivo],
                'avaliador2' => ['required', 'integer', $professorAtivo],
            ],
            AcaoTcc::Avaliar => array_filter([
                'nota_banca' => ['required', 'numeric', 'between:0,10'],
                // A coordenação informa de qual professor da banca é a nota.
                'id_professor' => $usuario->isCoordenacao() ? ['required', 'integer'] : null,
            ]),
            AcaoTcc::RegistrarAta => ['nota_minima' => ['required', 'numeric', 'between:0,10']],
            default => [],
        };

        $regras['nota'] = [$notaObrigatoria ? 'required' : 'nullable', 'string', 'max:4000'];
        if ($emNomeDeOutro) {
            $regras['confirmado'] = ['accepted'];
        }

        return $regras;
    }

    /**
     * Cria a proposta de TCC. O aluno propõe para si; a coordenação, para qualquer aluno.
     */
    public function criarProposta(Usuario $autor, array $dados): Tcc
    {
        if (! $autor->isAluno() && ! $autor->isCoordenacao()) {
            throw new AuthorizationException('Somente alunos e a coordenação criam propostas de TCC.');
        }

        $regras = $this->regrasProposta();
        if ($autor->isCoordenacao()) {
            $regras['id_aluno'] = ['required', 'integer', Rule::exists('Usuario', 'id')->where('tipo_usuario', TipoUsuario::Aluno->value)->where('ativo', true)];
        }
        $validos = $this->validar($dados, $regras);

        return DB::transaction(function () use ($autor, $validos) {
            $tcc = Tcc::create([
                ...$validos,
                'id_aluno' => $autor->isAluno() ? $autor->id : $validos['id_aluno'],
                'status' => TccStatus::Proposta,
                'aprovado' => StatusAprovacao::EsperandoResposta,
                'revisao' => 1,
            ]);
            $tcc->registrarEvento($autor, 'Proposta criada');

            return $tcc;
        });
    }

    /**
     * Edita a proposta enquanto ela ainda não foi enviada ao orientador.
     */
    public function atualizarProposta(Tcc $tcc, Usuario $autor, array $dados): Tcc
    {
        return DB::transaction(function () use ($tcc, $autor, $dados) {
            $tcc = Tcc::lockForUpdate()->findOrFail($tcc->getKey());

            if (! $autor->isCoordenacao() && $autor->id !== $tcc->id_aluno) {
                throw new AuthorizationException('Você não pode editar esta proposta.');
            }
            if ($tcc->status !== TccStatus::Proposta) {
                $this->falha('A proposta só pode ser editada antes de solicitar a orientação.');
            }
            $this->conferirRevisao($tcc, $dados);

            $tcc->fill($this->validar($dados, $this->regrasProposta()));
            $tcc->revisao++;
            $tcc->save();
            $tcc->registrarEvento($autor, 'Proposta atualizada');

            return $tcc;
        });
    }

    /**
     * Executa uma ação do fluxo. $dados deve trazer a revisao lida na tela.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function executar(Tcc $tcc, AcaoTcc $acao, Usuario $autor, array $dados): Tcc
    {
        $arquivoSalvo = null;

        try {
            return DB::transaction(function () use ($tcc, $acao, $autor, $dados, &$arquivoSalvo) {
                $tcc = Tcc::lockForUpdate()->findOrFail($tcc->getKey());

                if (! in_array($acao, $this->acoesDaEtapa($tcc->status), true)) {
                    $this->falha('Ação indisponível na etapa atual.');
                }
                if (! $this->pode($tcc, $acao, $autor)) {
                    throw new AuthorizationException('Esta etapa cabe a outra pessoa.');
                }
                $this->conferirRevisao($tcc, $dados);
                $validos = $this->validar($dados, $this->regras($tcc, $acao, $autor));
                $nota = $validos['nota'] ?? null;
                // Calculado antes da ação, porque depende da etapa atual.
                $emNomeDeOutro = $this->emNomeDeOutro($tcc, $acao, $autor);

                match ($acao) {
                    AcaoTcc::Solicitar => $this->solicitar($tcc, TipoSolicitacao::Orientador, $nota, TccStatus::AguardandoOrientador),
                    AcaoTcc::Aceitar => $tcc->status = TccStatus::AguardandoCoordenacao,
                    AcaoTcc::AprovarOrientacao => $this->responder($tcc, TipoSolicitacao::Orientador, $autor, true, $nota, TccStatus::EmOrientacao),
                    AcaoTcc::Recusar => $this->recusar($tcc, $autor, $nota),
                    AcaoTcc::Reuniao => $this->registrarReuniao($tcc, $autor, $validos),
                    AcaoTcc::Versao => $arquivoSalvo = $this->registrarVersao($tcc, $autor, $validos),
                    AcaoTcc::Liberar => $this->liberar($tcc),
                    AcaoTcc::SolicitarDefesa => $this->solicitarDefesa($tcc, $nota),
                    AcaoTcc::AutorizarDefesa => $this->autorizarDefesa($tcc, $autor, $nota),
                    AcaoTcc::Agendar => $this->agendar($tcc, $validos),
                    AcaoTcc::Avaliar => $this->avaliar($tcc, $autor, $validos),
                    AcaoTcc::RegistrarAta => $this->registrarAta($tcc, $validos),
                    AcaoTcc::Concluir => $this->concluir($tcc),
                };

                $tcc->revisao++;
                $tcc->save();

                $prefixo = $emNomeDeOutro ? 'Registrado pela coordenação em nome do responsável. ' : '';
                $tcc->registrarEvento($autor, $acao->label(), trim($prefixo.$nota));

                return $tcc;
            });
        } catch (Throwable $e) {
            if ($arquivoSalvo) {
                Storage::disk('local')->delete($arquivoSalvo);
            }

            throw $e;
        }
    }

    /**
     * Quem normalmente faz cada etapa.
     */
    private function ehResponsavel(Tcc $tcc, AcaoTcc $acao, Usuario $usuario): bool
    {
        $ehAluno = $usuario->isAluno() && $usuario->id === $tcc->id_aluno;
        $ehOrientador = $usuario->isProfessor() && $usuario->id === $tcc->id_professor_orientador;

        return match ($acao) {
            AcaoTcc::Solicitar, AcaoTcc::Versao, AcaoTcc::SolicitarDefesa => $ehAluno,
            AcaoTcc::Aceitar, AcaoTcc::Reuniao, AcaoTcc::Liberar => $ehOrientador,
            AcaoTcc::Recusar => in_array($tcc->status, [TccStatus::AguardandoOrientador, TccStatus::VersaoEmAnalise], true)
                ? $ehOrientador
                : $usuario->isCoordenacao(),
            AcaoTcc::Avaliar => $usuario->isProfessor() && $this->banca($tcc)?->participantes()->where('id_professor', $usuario->id)->exists(),
            AcaoTcc::AprovarOrientacao, AcaoTcc::AutorizarDefesa, AcaoTcc::Agendar,
            AcaoTcc::RegistrarAta, AcaoTcc::Concluir => $usuario->isCoordenacao(),
        };
    }

    private function solicitar(Tcc $tcc, TipoSolicitacao $tipo, ?string $nota, TccStatus $proxima): void
    {
        Solicitacao::create([
            'tipo_de_solicitacao' => $tipo,
            'id_aluno' => $tcc->id_aluno,
            'id_tcc' => $tcc->id,
            'status' => StatusAprovacao::EsperandoResposta,
            'descricao' => $nota,
        ]);
        $tcc->status = $proxima;
    }

    /**
     * Responde a solicitação em aberto do tipo indicado (RespostaSolicitacao).
     */
    private function responder(Tcc $tcc, TipoSolicitacao $tipo, Usuario $autor, bool $aprovada, ?string $parecer, TccStatus $proxima): void
    {
        $solicitacao = $tcc->solicitacoes()
            ->where('tipo_de_solicitacao', $tipo)
            ->where('status', StatusAprovacao::EsperandoResposta)
            ->latest('id')
            ->first();

        if ($solicitacao) {
            $resposta = RespostaSolicitacao::create([
                'id_solicitacao' => $solicitacao->id,
                'id_usuario' => $autor->id,
                'parecer' => $parecer,
            ]);
            $solicitacao->update([
                'id_resposta' => $resposta->id,
                'status' => $aprovada ? StatusAprovacao::Aprovado : StatusAprovacao::NaoAprovado,
            ]);
        }

        $tcc->status = $proxima;
    }

    /**
     * Devolução: volta à etapa apropriada (mesmas regras do Campus).
     */
    private function recusar(Tcc $tcc, Usuario $autor, ?string $motivo): void
    {
        match ($tcc->status) {
            TccStatus::VersaoEmAnalise => $tcc->status = TccStatus::EmOrientacao,
            TccStatus::DefesaSolicitada => $this->responder($tcc, TipoSolicitacao::Defesa, $autor, false, $motivo, TccStatus::LiberadoParaDefesa),
            default => $this->responder($tcc, TipoSolicitacao::Orientador, $autor, false, $motivo, TccStatus::Proposta),
        };
    }

    private function registrarReuniao(Tcc $tcc, Usuario $autor, array $validos): void
    {
        $tcc->logs()->create([
            'tipo' => TipoLog::Orientacao,
            'data_orientacao' => $validos['data'],
            'id_usuario' => $autor->id,
            'titulo' => 'Registro de orientação',
            'comentario' => $validos['nota'],
        ]);
    }

    /**
     * Cadastro formal do TCC / nova versão em PDF. Retorna o caminho salvo, para limpeza em caso de erro.
     */
    private function registrarVersao(Tcc $tcc, Usuario $autor, array $validos): string
    {
        /** @var UploadedFile $pdf */
        $pdf = $validos['pdf'];
        if (file_get_contents($pdf->getRealPath(), false, null, 0, 5) !== '%PDF-') {
            $this->falha('Envie um arquivo PDF válido.');
        }

        $caminho = Storage::disk('local')->putFileAs('tcc', $pdf, Str::uuid().'.pdf');
        $arquivo = Arquivo::create([
            'id_usuario' => $autor->id,
            'nome' => Str::limit(basename($pdf->getClientOriginalName()), 180, '') ?: 'tcc.pdf',
            'caminho' => $caminho,
            'sha256' => hash_file('sha256', $pdf->getRealPath()),
            'tamanho_bytes' => $pdf->getSize(),
        ]);

        $tcc->logs()->create([
            'tipo' => TipoLog::Versao,
            'id_usuario' => $autor->id,
            'arquivo' => $arquivo->id,
            'titulo' => $validos['titulo'],
            'comentario' => (string) ($validos['nota'] ?? ''),
        ]);

        $tcc->titulo = $validos['titulo'];
        $tcc->id_versao_liberada = null;
        $tcc->status = TccStatus::VersaoEmAnalise;

        return $caminho;
    }

    private function liberar(Tcc $tcc): void
    {
        $ultima = $tcc->versoes()->reorder()->latest('id')->first();
        if (! $ultima) {
            $this->falha('Cadastre o PDF do TCC antes de liberar.');
        }

        $tcc->id_versao_liberada = $ultima->id;
        $tcc->status = TccStatus::LiberadoParaDefesa;
    }

    private function solicitarDefesa(Tcc $tcc, ?string $nota): void
    {
        if (! $tcc->id_versao_liberada) {
            $this->falha('É preciso liberar uma versão do TCC antes de solicitar defesa.');
        }

        $this->solicitar($tcc, TipoSolicitacao::Defesa, $nota, TccStatus::DefesaSolicitada);
    }

    private function autorizarDefesa(Tcc $tcc, Usuario $autor, ?string $nota): void
    {
        $this->responder($tcc, TipoSolicitacao::Defesa, $autor, true, $nota, TccStatus::DefesaAutorizada);

        $solicitacao = $tcc->solicitacoes()->where('tipo_de_solicitacao', TipoSolicitacao::Defesa)->latest('id')->first();
        $tcc->defesas()->create([
            'id_aluno' => $tcc->id_aluno,
            'id_solicitacao' => $solicitacao?->id,
        ]);
    }

    private function agendar(Tcc $tcc, array $validos): void
    {
        $avaliadores = [(int) $validos['avaliador1'], (int) $validos['avaliador2']];
        if (count(array_unique([...$avaliadores, $tcc->id_professor_orientador])) !== 3) {
            $this->falha('A banca deve ter três professores distintos, incluindo o orientador.');
        }

        $defesa = $tcc->defesas()->latest('id')->firstOrFail();
        $defesa->update([
            'agendado_para' => $validos['data'].' '.$validos['hora'],
            'local' => $validos['local'],
        ]);

        $banca = $defesa->banca()->create();
        $banca->professores()->attach([
            $tcc->id_professor_orientador => ['funcao' => FuncaoBanca::Presidente->value],
            $avaliadores[0] => ['funcao' => FuncaoBanca::Avaliador->value],
            $avaliadores[1] => ['funcao' => FuncaoBanca::Avaliador->value],
        ]);

        $tcc->status = TccStatus::DefesaAgendada;
    }

    /**
     * Cada professor da banca registra a própria nota; pode corrigi-la até a ata ser registrada.
     */
    private function avaliar(Tcc $tcc, Usuario $autor, array $validos): void
    {
        $banca = $this->banca($tcc);
        $professor = $autor->isCoordenacao() ? (int) $validos['id_professor'] : $autor->id;

        if (! $banca->participantes()->where('id_professor', $professor)->exists()) {
            $this->falha('A nota deve ser de um professor desta banca.');
        }

        $avaliacao = Avaliacao::firstOrNew(['id_banca' => $banca->id, 'id_professor' => $professor]);
        $avaliacao->nota = $validos['nota_banca'];
        if ($avaliacao->exists) {
            $avaliacao->emitida_em = now();
        }
        $avaliacao->save();
    }

    /**
     * Média aritmética das três notas, arredondada a duas casas, comparada à média mínima.
     */
    private function registrarAta(Tcc $tcc, array $validos): void
    {
        $banca = $this->banca($tcc);
        $notas = $banca->avaliacoes()->get()->keyBy('id_professor');

        if ($notas->count() !== 3) {
            $this->falha('Aguardando as notas dos três professores da banca.');
        }

        // nota_1 é a do orientador (presidente); nota_2 e nota_3, dos avaliadores.
        $presidente = $notas->pull($tcc->id_professor_orientador);
        $avaliadores = $notas->sortKeys()->values();
        $media = round(((float) $presidente->nota + (float) $avaliadores[0]->nota + (float) $avaliadores[1]->nota) / 3, 2);

        $banca->ata()->create([
            'nota_1' => $presidente->id,
            'nota_2' => $avaliadores[0]->id,
            'nota_3' => $avaliadores[1]->id,
            'media_final' => $media,
            'nota_minima' => $validos['nota_minima'],
            'aprovado' => $media >= (float) $validos['nota_minima'],
        ]);

        $tcc->status = TccStatus::Avaliado;
    }

    private function concluir(Tcc $tcc): void
    {
        $ata = $this->banca($tcc)->ata;
        $ata->update(['homologada_em' => now()]);

        $tcc->aprovado = $ata->aprovado ? StatusAprovacao::Aprovado : StatusAprovacao::NaoAprovado;
        $tcc->status = TccStatus::Concluido;
    }

    private function banca(Tcc $tcc): ?Banca
    {
        return $tcc->defesas()->latest('id')->first()?->banca;
    }

    /**
     * @return array<string, mixed>
     */
    private function regrasProposta(): array
    {
        return [
            'id_professor_orientador' => ['required', 'integer', Rule::exists('Usuario', 'id')->where('tipo_usuario', TipoUsuario::Professor->value)->where('ativo', true)],
            'titulo' => ['required', 'string', 'max:200'],
            'resumo' => ['required', 'string', 'max:4000'],
        ];
    }

    private function conferirRevisao(Tcc $tcc, array $dados): void
    {
        if ((int) ($dados['revisao'] ?? 0) !== $tcc->revisao) {
            $this->falha('Este TCC mudou em outra aba. Atualize a página antes de continuar.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(array $dados, array $regras): array
    {
        return Validator::make($dados, $regras)->validate();
    }

    private function falha(string $mensagem): never
    {
        throw ValidationException::withMessages(['acao' => $mensagem]);
    }
}
