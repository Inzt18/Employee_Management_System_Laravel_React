<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'Pending';
    case Ongoing = 'Ongoing';
    case Completed = 'Completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
