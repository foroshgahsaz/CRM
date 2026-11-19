<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class StaffMobileRegistry
{
    public static function releaseFromTrashedAccounts(string $mobile): void
    {
        User::onlyTrashed()->where('mobile', $mobile)->update(['mobile' => null]);
    }

    public static function assertAvailableForNewUser(string $mobile, ?int $exceptUserId = null): void
    {
        self::releaseFromTrashedAccounts($mobile);

        $query = User::query()->where('mobile', $mobile);

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'data.mobile' => 'این شماره برای کاربر دیگر پنل ثبت شده است. موبایل مشتری و درخواست‌های عمومی جدا هستند؛ اگر در لیست نمی‌بینید، با مدیر جستجو کنید یا موبایل آن کاربر را در ویرایش عوض کنید.',
            ]);
        }
    }

    public static function isTakenByActiveStaff(string $mobile, ?int $exceptUserId = null): bool
    {
        self::releaseFromTrashedAccounts($mobile);

        $query = User::query()->where('mobile', $mobile);

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        return $query->exists();
    }
}