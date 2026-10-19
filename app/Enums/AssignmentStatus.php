<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Transferred = 'transferred';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Completed => 'تکمیل شده',
            self::Transferred => 'منتقل شده',
            self::Cancelled => 'لغو شده',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Active;
    }
}
