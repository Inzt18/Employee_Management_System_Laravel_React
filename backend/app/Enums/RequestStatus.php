<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Pending = 'Pending';
    case Approved = 'Approved';
    case Rejected = 'Rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuses an administrator may set when reviewing a request.
     */
    public static function decisions(): array
    {
        return [self::Approved->value, self::Rejected->value];
    }
}
