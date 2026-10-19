<?php

namespace App\Enums;

enum AttachmentCategory: string
{
    case CustomerInitial = 'customer_initial';
    case CustomerSupplement = 'customer_supplement';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::CustomerInitial => 'مدرک اولیه مشتری',
            self::CustomerSupplement => 'مدرک تکمیلی مشتری',
            self::Internal => 'پیوست داخلی',
        };
    }
}
