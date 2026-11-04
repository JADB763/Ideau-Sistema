<?php

namespace App\Console\Commands;

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use App\Rules\Cpf;
use App\Support\Cpf as CpfSupport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Cria contas pelo terminal. Usado para a primeira conta da coordenação;
 * as demais são cadastradas pela coordenação no sistema.
 */
class CriarUsuario extends Command
{
    /**
     * @var string
     */
    protected $signature = 'usuario:criar';

    /**
     * @var string
     */
    protected $description = 'Cria uma conta de usuário (ex.: a primeira conta da coordenação)';

    public function handle(): int
    {
        $tipo = select(
            label: 'Tipo de usuário',
            options: collect(TipoUsuario::cases())->mapWithKeys(fn (TipoUsuario $t) => [$t->name => $t->value])->all(),
            default: TipoUsuario::Coordenacao->name,
        );

        $dados = [
            'nome_completo' => text('Nome completo', required: true),
            'cpf' => CpfSupport::normalizar(text('CPF', placeholder: '000.000.000-00', required: true)),
            'data_nascimento' => text('Data de nascimento', placeholder: 'dd/mm/aaaa', required: true),
            'email' => text('E-mail', required: true),
            'senha' => password('Senha (10 a 72 caracteres)', required: true),
            'senha_confirmation' => password('Repita a senha', required: true),
        ];

        $validator = Validator::make($dados, [
            'nome_completo' => ['required', 'string', 'max:180'],
            'cpf' => ['required', new Cpf, Rule::unique('Usuario', 'cpf')],
            'data_nascimento' => ['required', 'date_format:d/m/Y', 'before:today'],
            'email' => ['required', 'email', 'max:254', Rule::unique('Usuario', 'email')],
            'senha' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        $usuario = Usuario::create([
            'nome_completo' => $dados['nome_completo'],
            'cpf' => $dados['cpf'],
            'data_nascimento' => Carbon::createFromFormat('d/m/Y', $dados['data_nascimento'])->startOfDay(),
            'email' => strtolower($dados['email']),
            'senha' => $dados['senha'],
            'tipo_usuario' => constant(TipoUsuario::class.'::'.$tipo),
        ]);

        $this->info("Conta criada: {$usuario->nome_completo} ({$usuario->tipo_usuario->value}). Entre com o CPF ".CpfSupport::formatar($usuario->cpf).'.');

        return self::SUCCESS;
    }
}
