<?php

namespace App\Support;

class PermissionCatalog
{
    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            'requests.view_all' => 'مشاهده همه درخواست‌ها',
            'requests.view_related' => 'مشاهده درخواست‌های مرتبط',
            'requests.view_sensitive' => 'مشاهده اطلاعات حساس پرونده',
            'requests.create_internal' => 'ثبت درخواست داخلی',
            'requests.assign' => 'تخصیص اولیه درخواست',
            'requests.reassign' => 'ارجاع مجدد درخواست',
            'requests.reply' => 'ثبت توضیح کارشناسی',
            'requests.add_internal_note' => 'ثبت یادداشت داخلی',
            'requests.add_admin_note' => 'ثبت یادداشت مدیریتی',
            'requests.request_customer_info' => 'درخواست مدرک از مشتری',
            'requests.upload_internal_attachment' => 'بارگذاری پیوست داخلی',
            'requests.complete_assignment' => 'تکمیل کارشناسی',
            'requests.approve' => 'تأیید نهایی',
            'requests.reject_approval' => 'رد تأیید و بازگشت برای اصلاح',
            'requests.reopen' => 'بازگشایی پرونده',
            'requests.close' => 'بستن پرونده',
            'requests.archive' => 'بایگانی پرونده',
            'requests.export' => 'خروجی گزارش درخواست‌ها',
            'users.view' => 'مشاهده کاربران',
            'users.manage' => 'مدیریت کاربران',
            'roles.manage' => 'مدیریت نقش‌ها و مجوزها',
            'units.manage' => 'مدیریت واحدهای سازمانی',
            'request_types.manage' => 'مدیریت انواع درخواست',
            'form_fields.manage' => 'مدیریت فیلدهای فرم',
            'settings.manage' => 'مدیریت تنظیمات',
            'sms_settings.manage' => 'مدیریت پیامک',
            'audit_logs.view' => 'مشاهده سوابق حسابرسی',
            'reports.view' => 'مشاهده گزارش‌ها',
            'sla.manage' => 'مدیریت مهلت رسیدگی',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return [
            'super_admin' => 'مدیر ارشد سامانه',
            'system_administrator' => 'مدیر عملیاتی',
            'expert' => 'کارشناس',
            'auditor' => 'ناظر',
        ];
    }

    /**
     * @return list<string>
     */
    public static function forRole(string $role): array
    {
        $all = array_keys(self::permissions());

        return match ($role) {
            'super_admin' => $all,
            'system_administrator' => [
                'requests.view_all',
                'requests.view_related',
                'requests.view_sensitive',
                'requests.create_internal',
                'requests.assign',
                'requests.reassign',
                'requests.reply',
                'requests.add_internal_note',
                'requests.add_admin_note',
                'requests.request_customer_info',
                'requests.upload_internal_attachment',
                'requests.complete_assignment',
                'requests.approve',
                'requests.reject_approval',
                'requests.reopen',
                'requests.close',
                'requests.archive',
                'requests.export',
                'users.view',
                'reports.view',
                'sla.manage',
            ],
            'expert' => [
                'requests.view_related',
                'requests.reply',
                'requests.add_internal_note',
                'requests.request_customer_info',
                'requests.upload_internal_attachment',
                'requests.complete_assignment',
                'requests.reassign',
            ],
            'auditor' => [
                'requests.view_all',
                'requests.view_related',
                'requests.view_sensitive',
                'reports.view',
                'audit_logs.view',
            ],
            default => [],
        };
    }
}
