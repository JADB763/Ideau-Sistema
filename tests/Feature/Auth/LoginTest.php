<?php

use App\Models\Usuario;
use App\Support\Cpf;

it('redireciona visitantes para o login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('mostra a tela de login', function () {
    $this->get('/login')->assertOk()->assertSee('CPF');
});

it('entra com CPF (com ou sem máscara) e senha', function (bool $mascara) {
    $usuario = Usuario::factory()->create();
    $cpf = $mascara ? Cpf::formatar($usuario->cpf) : $usuario->cpf;

    $this->post('/login', ['cpf' => $cpf, 'senha' => 'password'])
        ->assertRedirect(route('inicio'));

    $this->assertAuthenticatedAs($usuario);
})->with(['com máscara' => true, 'sem máscara' => false]);

it('recusa senha errada', function () {
    $usuario = Usuario::factory()->create();

    $this->post('/login', ['cpf' => $usuario->cpf, 'senha' => 'errada'])
        ->assertSessionHasErrors(['cpf' => 'CPF ou senha incorretos.']);

    $this->assertGuest();
});

it('recusa conta inativa com a mesma mensagem', function () {
    $usuario = Usuario::factory()->inativo()->create();

    $this->post('/login', ['cpf' => $usuario->cpf, 'senha' => 'password'])
        ->assertSessionHasErrors(['cpf' => 'CPF ou senha incorretos.']);

    $this->assertGuest();
});

it('bloqueia após 10 tentativas erradas, mesmo com a senha certa depois', function () {
    $usuario = Usuario::factory()->create();

    foreach (range(1, 10) as $_) {
        $this->post('/login', ['cpf' => $usuario->cpf, 'senha' => 'errada']);
    }

    $this->post('/login', ['cpf' => $usuario->cpf, 'senha' => 'password'])
        ->assertSessionHasErrors('cpf');

    expect(session('errors')->first('cpf'))->toContain('Muitas tentativas');
    $this->assertGuest();
});

it('sai do sistema', function () {
    $this->actingAs(Usuario::factory()->create())
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('encerra a sessão de quem foi desativado durante o uso', function () {
    $usuario = Usuario::factory()->create();
    $this->actingAs($usuario)->get('/')->assertOk();

    $usuario->update(['ativo' => false]);

    $this->get('/')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('mostra o painel com o nome e o perfil', function () {
    $usuario = Usuario::factory()->professor()->create(['nome_completo' => 'Ana Souza']);

    $this->actingAs($usuario)->get('/')
        ->assertOk()
        ->assertSee('Olá, Ana.')
        ->assertSee('Professor');
});

it('envia os cabeçalhos de segurança', function () {
    $this->get('/login')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy');
});
