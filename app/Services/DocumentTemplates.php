<?php

namespace App\Services;

use App\Enums\DocumentoTipo;
use App\Models\Estagio;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Modelos de documento em resources/modelos (.txt UTF-8) com marcadores como {{aluno.nome}}
 * (helpers/templates.php do Campus).
 */
class DocumentTemplates
{
    /**
     * @return array<string, array{arquivo: string, descricao: string}>
     */
    public function modelos(): array
    {
        return [
            DocumentoTipo::PlanoDeAtividades->value => ['arquivo' => 'plano.txt', 'descricao' => 'Identificação e planejamento do estágio, com atividades cadastradas.'],
            DocumentoTipo::RelatorioDeEstagio->value => ['arquivo' => 'relatorio.txt', 'descricao' => 'Identificação automática e espaço para relatar atividades efetivamente realizadas.'],
            DocumentoTipo::FichaDeAvaliacao->value => ['arquivo' => 'avaliacao.txt', 'descricao' => 'Dados do aluno e do estágio preenchidos; parecer a ser informado pelo avaliador.'],
            DocumentoTipo::SolicitacaoDeEncerramento->value => ['arquivo' => 'encerramento.txt', 'descricao' => 'Requerimento preenchido com os dados do vínculo e espaço para justificativa.'],
        ];
    }

    public function fonte(DocumentoTipo $tipo): string
    {
        return File::get(resource_path('modelos/'.$this->modelos()[$tipo->value]['arquivo']));
    }

    /**
     * Dados do estágio no momento da versão. As chaves seguem os marcadores dos modelos.
     *
     * @return array<string, array<string, mixed>>
     */
    public function snapshot(Estagio $estagio): array
    {
        $estagio->loadMissing('aluno', 'empresa', 'orientador');

        return [
            'aluno' => [
                'nome' => $estagio->aluno->nome_completo,
                'email' => $estagio->aluno->email,
                'matricula' => $estagio->aluno->matricula,
                'curso' => $estagio->aluno->curso,
                'instituicao' => $estagio->aluno->instituicao,
            ],
            'empresa' => [
                'nome' => $estagio->empresa->nome,
                'email' => $estagio->empresa->email,
                'cnpj' => $estagio->empresa->cnpj,
                'representante' => $estagio->empresa->representante,
                'endereco' => $estagio->empresa->endereco,
            ],
            'orientador' => [
                'nome' => $estagio->orientador?->nome_completo,
                'email' => $estagio->orientador?->email,
                'area' => $estagio->orientador?->area,
            ],
            'estagio' => [
                'supervisor' => $estagio->supervisor,
                'supervisorEmail' => $estagio->supervisor_email,
                'inicio' => $estagio->data_inicio?->format('Y-m-d'),
                'fim' => $estagio->data_termino?->format('Y-m-d'),
                'horas' => $estagio->horas_semanais === null ? null : str_replace('.', ',', (string) (float) $estagio->horas_semanais),
                'atividades' => $estagio->atividades,
            ],
        ];
    }

    /**
     * Preenche o modelo. Bloqueia a geração se faltar algum dado exigido.
     *
     * @return array{modelo: string, modelo_hash: string, texto_preenchido: string}
     *
     * @throws ValidationException
     */
    public function preencher(DocumentoTipo $tipo, array $snapshot, ?string $observacoes): array
    {
        $fonte = $this->fonte($tipo);
        $valores = $this->valores($snapshot, (string) $observacoes);

        $texto = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function (array $m) use ($valores) {
            if (! array_key_exists($m[1], $valores) || trim($valores[$m[1]]) === '') {
                throw ValidationException::withMessages(['acao' => 'Complete o cadastro antes de gerar o documento: '.$m[1].'.']);
            }

            return $valores[$m[1]];
        }, $fonte);

        return ['modelo' => $tipo->value, 'modelo_hash' => hash('sha256', $fonte), 'texto_preenchido' => $texto];
    }

    /**
     * @return array<string, string>
     */
    private function valores(array $snapshot, string $observacoes): array
    {
        $mapa = [];
        foreach (['aluno', 'empresa', 'orientador', 'estagio'] as $grupo) {
            foreach ($snapshot[$grupo] ?? [] as $chave => $valor) {
                if (! is_scalar($valor)) {
                    continue;
                }
                if ($grupo === 'estagio' && in_array($chave, ['inicio', 'fim'], true)) {
                    $valor = date('d/m/Y', strtotime($valor));
                }
                $mapa["$grupo.$chave"] = (string) $valor;
            }
        }
        $mapa['observacoes'] = trim($observacoes) !== '' ? $observacoes : 'Não informado nesta requisição.';

        return $mapa;
    }
}
