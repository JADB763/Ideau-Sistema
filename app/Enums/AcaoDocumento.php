<?php

namespace App\Enums;

/**
 * Ações do fluxo dos documentos de estágio (helpers/domain.php do Campus).
 */
enum AcaoDocumento: string
{
    case Enviar = 'enviar';
    case Aprovar = 'aprovar';
    case Devolver = 'devolver';
    case Revisar = 'revisar';

    public function label(): string
    {
        return match ($this) {
            self::Enviar => 'Encaminhar para análise',
            self::Aprovar => 'Aprovar',
            self::Devolver => 'Devolver para ajustes',
            self::Revisar => 'Criar nova versão',
        };
    }
}
