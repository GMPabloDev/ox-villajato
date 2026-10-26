<?php

namespace App\Enums;

enum PropertyType: string
{
    case House = 'house';
    case Apartment = 'apartment';

    public function label(): string
    {
        return match ($this) {
            self::House => 'Casa',
            self::Apartment => 'Departamento',
        };
    }
}
