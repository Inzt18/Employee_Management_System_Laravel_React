<?php

namespace App\Enums;

enum Role: string
{
    case Employee = 'Employee';
    case Admin = 'Admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
