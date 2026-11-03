<?php

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

function responderCriarUsuario(array $respostas = [])
{
    $r = array_merge([
        'tipo' => 'Coordenacao',
        'nome' => 'Maria Coordenadora',
        'cpf' => '529.982.247-25',
        'nascimento' => '10/05/1980',
        'email' => 'Maria@Ideau.edu.br',
        'senha' => 'senha-segura-123',
        'confirmacao' => 'senha-segura-123',
    ], $respostas);

    return test()->artisan('usuario:criar')
        ->expectsChoice('Tipo de usuário', $r['tipo'], collect(TipoUsuario::cases())->mapWithKeys(fn ($t) => [$t->name => $t->value])->all())
        ->expectsQuestion('Nome completo', $r['nome'])
        ->expectsQuestion('CPF', $r['cpf'])
        ->expectsQuestion('Data de nascimento', $r['nascimento'])
        ->expectsQuestion('E-mail', $r['email'])
        ->expectsQuestion('Senha (10 a 72 caracteres)', $r['senha'])
        ->expectsQuestion('Repita a senha', $r['confirmacao']);
}

it('cria a conta da coordenação', function () {
    responderCriarUsuario()
        ->expectsOutputToContain('Entre com o CPF 529.982.247-25')
        ->assertSuccessful();

    $usuario = Usuario::firstWhere('cpf', '52998224725');

    expect($usuario->tipo_usuario)->toBe(TipoUsuario::Coordenacao)
        ->and($usuario->email)->toBe('maria@ideau.edu.br')
        ->and($usuario->data_nascimento->format('Y-m-d'))->toBe('1980-05-10')
        ->and(Hash::check('senha-segura-123', $usuario->senha))->toBeTrue();
});

it('recusa dados inválidos sem criar a conta', function (array $respostas, string $erro) {
    Usuario::factory()->create(['cpf' => '11144477735']);

    responderCriarUsuario($respostas)
        ->expectsOutputToContain($erro)
        ->assertFailed();

    expect(Usuario::count())->toBe(1);
})->with([
    'CPF inválido' => [['cpf' => '529.982.247-24'], 'O CPF informado não é válido.'],
    'CPF repetido' => [['cpf' => '111.444.777-35'], 'Já existe um cadastro com este CPF.'],
    'senha curta' => [['senha' => 'curta', 'confirmacao' => 'curta'], 'no mínimo 10 caracteres'],
    'data inválida' => [['nascimento' => '1980-05-10'], 'formato d/m/Y'],
]);
