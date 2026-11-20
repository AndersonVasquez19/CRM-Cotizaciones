<?php

namespace App\Enums;

enum MeasurementUnit: string
{
    case UND = 'UND';
    case MTR = 'MTR';
    case PTO = 'PTO';
    case CJA = 'CJA';
    case GLB = 'GLB';
    case HRS = 'HRS';

    public function label(): string
    {
        return match ($this) {
            self::UND => 'Unidad',
            self::MTR => 'Metro',
            self::PTO => 'Punto',
            self::CJA => 'Caja',
            self::GLB => 'Global',
            self::HRS => 'Horas',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
