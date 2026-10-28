<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Available = 'available';
    case Sold = 'sold';
    case Rented = 'rented';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Sold => 'Vendida',
            self::Rented => 'Alquilada',
        };
    }

    public function actionLabel(PropertyOperation $operation): string
    {
        return match ($this) {
            self::Available => match ($operation) {
                PropertyOperation::Sale => 'Marcar como vendida',
                PropertyOperation::Rent => 'Marcar como alquilada',
            },
            self::Sold, self::Rented => 'Volver a disponible',
        };
    }

    public function action(): string
    {
        return match ($this) {
            self::Available => 'close',
            self::Sold, self::Rented => 'reopen',
        };
    }
}
