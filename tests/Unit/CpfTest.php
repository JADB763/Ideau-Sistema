<?php

use App\Support\Cpf;

it('aceita CPFs válidos com ou sem máscara', function (string $cpf) {
    expect(Cpf::valido($cpf))->toBeTrue();
})->with(['529.982.247-25', '52998224725', '111.444.777-35']);

it('recusa CPFs inválidos', function (?string $cpf) {
    expect(Cpf::valido($cpf))->toBeFalse();
})->with(['529.982.247-24', '111.111.111-11', '1234567890', '', null]);

it('normaliza e formata', function () {
    expect(Cpf::normalizar(' 529.982.247-25 '))->toBe('52998224725')
        ->and(Cpf::formatar('52998224725'))->toBe('529.982.247-25');
});
