<?php

namespace App\Enums;

enum MessageType: string
{
    case ExpertNote = 'expert_note';
    case ReferralInstruction = 'referral_instruction';
    case InfoRequest = 'info_request';
    case ReviewResult = 'review_result';
    case AdminNote = 'admin_note';
    case CustomerVisible = 'customer_visible';
    case Correction = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::ExpertNote => 'توضیح کارشناسی',
            self::ReferralInstruction => 'دستور ارجاع',
            self::InfoRequest => 'درخواست اطلاعات تکمیلی',
            self::ReviewResult => 'نتیجه بررسی',
            self::AdminNote => 'یادداشت مدیریتی',
            self::CustomerVisible => 'پیام مشتری',
            self::Correction => 'اصلاحیه',
        };
    }
}
