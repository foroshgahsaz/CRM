<?php

namespace App\Enums;

enum MessageVisibility: string
{
    case Internal = 'internal';
    case AdminOnly = 'admin_only';
    case CustomerVisible = 'customer_visible';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'داخلی',
            self::AdminOnly => 'فقط مدیران',
            self::CustomerVisible => 'قابل نمایش به مشتری',
        };
    }
}
