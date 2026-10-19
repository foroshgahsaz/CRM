<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class JalaliDate
{
    public static function format(CarbonInterface|string|null $value, string $format = 'Y/m/d H:i'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return Jalalian::fromCarbon($date)->format($format);
    }

    public static function formatDate(CarbonInterface|string|null $value): string
    {
        return self::format($value, 'Y/m/d');
    }

    public static function toGregorianDate(string $value): ?string
    {
        $value = trim(MobileNumber::toEnglishDigits($value));
        $value = str_replace('-', '/', $value);

        if (! preg_match('/^(1[3-4]\d{2})\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12]\d|3[01])$/', $value, $matches)) {
            return null;
        }

        try {
            return Jalalian::fromFormat(
                'Y/n/j',
                sprintf('%s/%s/%s', $matches[1], (int) $matches[2], (int) $matches[3]),
            )->toCarbon()->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];
    }
}
