<?php

namespace App\Enums;

enum AttachmentVisibility: string
{
    case CustomerVisible = 'customer_visible';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::CustomerVisible => 'قابل نمایش به مشتری',
            self::Internal => 'داخلی',
        };
    }
}
