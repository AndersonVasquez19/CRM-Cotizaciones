<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Borrador = 'borrador';
    case PendienteAprobacion = 'pendiente_aprobacion';
    case Enviada = 'enviada';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Vencida = 'vencida';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::PendienteAprobacion => 'Pendiente Aprobación',
            self::Enviada => 'Enviada',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
            self::Vencida => 'Vencida',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
