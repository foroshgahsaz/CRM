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
            'warehouses.manage' => 'تعریف و ویرایش انبار',
            'inventory.view' => 'مشاهده و جستجوی موجودی انبار',
            'inventory.manage' => 'افزودن و ویرایش موجودی انبار',
            'inventory.allocate' => 'ارجاع کالای انبار به درخواست',
            'system.delete_any' => 'حذف کامل هر رکورد (درخواست، کاربر، تنظیمات و سوابق)',
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
            'warehouse_manager' => 'مدیر انبار',
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
                'inventory.view',
                'inventory.allocate',
            ],
            'expert' => [
                'requests.view_related',
                'requests.reply',
                'requests.add_internal_note',
                'requests.request_customer_info',
                'requests.upload_internal_attachment',
                'requests.complete_assignment',
                'requests.reassign',
                'inventory.view',
            ],
            'auditor' => [
                'requests.view_all',
                'requests.view_related',
                'requests.view_sensitive',
                'reports.view',
                'audit_logs.view',
                'inventory.view',
            ],
            'warehouse_manager' => [
                'warehouses.manage',
                'inventory.view',
                'inventory.manage',
                'inventory.allocate',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, array{label: string, description?: string, permissions: list<string>}>
     */
    public static function permissionGroups(): array
    {
        $names = function (string ...$prefixes): array {
            return array_values(array_filter(
                array_keys(self::permissions()),
                function (string $key) use ($prefixes): bool {
                    foreach ($prefixes as $prefix) {
                        if (str_starts_with($key, $prefix)) {
                            return true;
                        }
                    }

                    return false;
                },
            ));
        };

        return [
            'requests' => [
                'label' => 'درخواست‌ها',
                'description' => 'مشاهده، ارجاع، رسیدگی، تأیید و بایگانی پرونده‌ها',
                'permissions' => $names('requests.'),
            ],
            'organization' => [
                'label' => 'کاربران و سازمان',
                'description' => 'کاربران، نقش‌ها و واحدهای سازمانی',
                'permissions' => $names('users.', 'roles.', 'units.'),
            ],
            'warehouse' => [
                'label' => 'انبار',
                'permissions' => $names('warehouses.', 'inventory.'),
            ],
            'configuration' => [
                'label' => 'تنظیمات و فرم',
                'permissions' => $names('request_types.', 'form_fields.', 'settings.', 'sms_settings.', 'sla.'),
            ],
            'oversight' => [
                'label' => 'گزارش و حسابرسی',
                'permissions' => $names('audit_logs.', 'reports.'),
            ],
            'system' => [
                'label' => 'سیستم',
                'permissions' => $names('system.'),
            ],
        ];
    }
}
