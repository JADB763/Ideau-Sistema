<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

it('mostra os dados da conta com o CPF formatado', function () {
    $usuario = Usuario::factory()->create(['cpf' => '52998224725']);

    $this->actingAs($usuario)->get('/conta')
        ->assertOk()
        ->assertSee('529.982.247-25');
});

it('troca a senha e continua conectado', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)->put('/conta/senha', [
        'senha_atual' => 'password',
        'senha' => 'nova-senha-123',
        'senha_confirmation' => 'nova-senha-123',
    ])->assertRedirect(route('conta'));

    expect(Hash::check('nova-senha-123', $usuario->fresh()->senha))->toBeTrue();
    $this->get('/')->assertOk();
});

it('recusa senha atual errada', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)->put('/conta/senha', [
        'senha_atual' => 'errada',
        'senha' => 'nova-senha-123',
        'senha_confirmation' => 'nova-senha-123',
    ])->assertSessionHasErrors(['senha_atual' => 'A senha atual não confere.']);

    expect(Hash::check('password', $usuario->fresh()->senha))->toBeTrue();
});

it('exige senha nova entre 10 e 72 caracteres e confirmada', function (string $nova, string $confirmacao) {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)->put('/conta/senha', [
        'senha_atual' => 'password',
        'senha' => $nova,
        'senha_confirmation' => $confirmacao,
    ])->assertSessionHasErrors('senha');
})->with([
    'curta' => ['curta', 'curta'],
    'longa' => [str_repeat('a', 73), str_repeat('a', 73)],
    'sem confirmação' => ['nova-senha-123', 'outra-senha-123'],
]);

it('encerra outras sessões quando a senha muda', function () {
    $usuario = Usuario::factory()->create();
    $this->actingAs($usuario)->get('/')->assertOk();

    // Senha trocada em outra sessão: o hash guardado nesta deixa de conferir.
    $usuario->update(['senha' => 'trocada-em-outro-lugar']);

    $this->get('/')->assertRedirect(route('login'));
    $this->assertGuest();
});
