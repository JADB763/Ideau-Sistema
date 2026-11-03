<?php

namespace App\Support;

class Cpf
{
    /**
     * Remove pontos, traço e espaços: "123.456.789-09" -> "12345678909".
     */
    public static function normalizar(?string $cpf): string
    {
        return preg_replace('/\D/', '', (string) $cpf);
    }

    /**
     * Confere tamanho e dígitos verificadores.
     */
    public static function valido(?string $cpf): bool
    {
        $cpf = self::normalizar($cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        foreach ([9, 10] as $posicao) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * ($posicao + 1 - $i);
            }
            $digito = ($soma * 10) % 11 % 10;

            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    /**
     * "12345678909" -> "123.456.789-09".
     */
    public static function formatar(?string $cpf): string
    {
        $cpf = self::normalizar($cpf);

        return strlen($cpf) === 11
            ? substr($cpf, 0, 3).'.'.substr($cpf, 3, 3).'.'.substr($cpf, 6, 3).'-'.substr($cpf, 9, 2)
            : $cpf;
    }
}
