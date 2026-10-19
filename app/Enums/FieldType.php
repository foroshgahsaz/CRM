<?php

namespace App\Enums;

enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Boolean = 'boolean';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'متن کوتاه',
            self::Textarea => 'متن بلند',
            self::Number => 'عدد',
            self::Date => 'تاریخ',
            self::Select => 'انتخابی',
            self::Boolean => 'بله یا خیر',
        };
    }
}
