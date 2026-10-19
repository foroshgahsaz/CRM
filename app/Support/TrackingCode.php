<?php

namespace App\Support;

use App\Models\ServiceRequest;
use RuntimeException;

class TrackingCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = 'RQ-'.$this->random(10);

            if (! ServiceRequest::query()->where('tracking_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('تولید کد رهگیری یکتا ناموفق بود.');
    }

    public static function normalize(?string $value): string
    {
        $value = strtoupper(trim(MobileNumber::toEnglishDigits((string) $value)));
        $value = str_replace([' ', '–', '—'], '', $value);

        if ($value !== '' && ! str_starts_with($value, 'RQ-') && preg_match('/^[A-Z0-9]{10}$/', $value)) {
            $value = 'RQ-'.$value;
        }

        return $value;
    }

    private function random(int $length): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $output = '';

        for ($i = 0; $i < $length; $i++) {
            $output .= self::ALPHABET[random_int(0, $max)];
        }

        return $output;
    }
}
