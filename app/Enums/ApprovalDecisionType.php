<?php

namespace App\Enums;

enum ApprovalDecisionType: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'تأیید',
            self::Rejected => 'رد و بازگشت برای اصلاح',
        };
    }
}
