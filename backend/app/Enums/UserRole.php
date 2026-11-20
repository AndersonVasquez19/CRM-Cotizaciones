<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Comercial = 'comercial';
    case Tecnico = 'tecnico';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Comercial => 'Ejecutivo Comercial',
            self::Tecnico => 'Especialista Técnico',
            self::Supervisor => 'Supervisor de Operaciones',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
