<?php

namespace App\Enums;

enum CrmBackupScope: string
{
    case Full = 'full';
    case Database = 'database';
    case Files = 'files';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'کامل (پایگاه + فایل)',
            self::Database => 'فقط پایگاه داده',
            self::Files => 'فقط فایل‌ها',
        };
    }
}