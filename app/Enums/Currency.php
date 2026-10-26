<?php

namespace App\Enums;

enum Currency: string
{
    case Pen = 'PEN';
    case Usd = 'USD';

    public function symbol(): string
    {
        return match ($this) {
            self::Pen => 'S/',
            self::Usd => 'US$',
        };
    }
}
