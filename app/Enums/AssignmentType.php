<?php

namespace App\Enums;

enum AssignmentType: string
{
    case Initial = 'initial';
    case Reassignment = 'reassignment';
    case UnitTransfer = 'unit_transfer';
    case Claim = 'claim';
    case Correction = 'correction';
    case Reopen = 'reopen';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'تخصیص اولیه',
            self::Reassignment => 'ارجاع مجدد',
            self::UnitTransfer => 'انتقال به واحد',
            self::Claim => 'پذیرش در واحد',
            self::Correction => 'اصلاح پس از رد تأیید',
            self::Reopen => 'بازگشایی',
        };
    }
}
