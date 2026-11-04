<?php

namespace App\Enums;

/**
 * Ações do fluxo do TCC (helpers/domain.php do Campus).
 */
enum AcaoTcc: string
{
    case Solicitar = 'solicitar';
    case Aceitar = 'aceitar';
    case AprovarOrientacao = 'aprovar-orientacao';
    case Recusar = 'recusar';
    case Reuniao = 'reuniao';
    case Versao = 'versao';
    case Liberar = 'liberar';
    case SolicitarDefesa = 'solicitar-defesa';
    case AutorizarDefesa = 'autorizar-defesa';
    case Agendar = 'agendar';
    case Avaliar = 'avaliar';
    case RegistrarAta = 'registrar-ata';
    case Concluir = 'concluir';

    public function label(): string
    {
        return match ($this) {
            self::Solicitar => 'Solicitar orientação',
            self::Aceitar => 'Aceitar orientação',
            self::AprovarOrientacao => 'Aprovar orientação',
            self::Recusar => 'Devolver',
            self::Reuniao => 'Registrar orientação',
            self::Versao => 'Cadastrar TCC / nova versão PDF',
            self::Liberar => 'Liberar para defesa',
            self::SolicitarDefesa => 'Solicitar defesa',
            self::AutorizarDefesa => 'Autorizar defesa',
            self::Agendar => 'Agendar defesa e banca',
            self::Avaliar => 'Registrar nota',
            self::RegistrarAta => 'Registrar ata e resultado',
            self::Concluir => 'Homologar resultado',
        };
    }
}
