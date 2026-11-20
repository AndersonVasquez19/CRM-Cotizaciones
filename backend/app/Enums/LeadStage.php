<?php

namespace App\Enums;

enum LeadStage: string
{
    case Nuevo = 'nuevo';
    case VisitaTecnica = 'visita_tecnica';
    case Cotizado = 'cotizado';
    case Negociacion = 'negociacion';
    case Ganado = 'ganado';
    case Perdido = 'perdido';

    public function label(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo',
            self::VisitaTecnica => 'Visita Técnica',
            self::Cotizado => 'Cotizado',
            self::Negociacion => 'Negociación',
            self::Ganado => 'Ganado',
            self::Perdido => 'Perdido',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
