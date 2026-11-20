<?php

namespace App\Enums;

enum CatalogItemType: string
{
    case Equipo = 'equipo';
    case Material = 'material';
    case Servicio = 'servicio';
    case Mantenimiento = 'mantenimiento';

    public function label(): string
    {
        return match ($this) {
            self::Equipo => 'Equipo',
            self::Material => 'Material',
            self::Servicio => 'Servicio',
            self::Mantenimiento => 'Mantenimiento',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
