<?php

namespace App\Enums;

enum SmsStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'در صف',
            self::Sent => 'ارسال شده',
            self::Failed => 'ناموفق',
            self::Skipped => 'ارسال نشده',
        };
    }
}
