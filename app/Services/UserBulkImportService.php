<?php

namespace App\Services;

use App\Models\User;
use App\Support\MobileNumber;
use App\Support\RoleLabelResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserBulkImportService
{
    /**
     * @var list<string>
     */
    private const BLOCKED_ROLE_NAMES = ['super_admin'];

    public function __construct(
        private UserSpreadsheetReader $reader,
        private AuditLogService $auditLog,
    ) {}

    public function import(string $absolutePath, string $password, bool $active, User $actor): UserBulkImportResult
    {
        $rows = $this->reader->rows($absolutePath);

        if ($rows === []) {
            return new UserBulkImportResult(failures: [
                ['row' => 0, 'message' => 'فایل خالی است.'],
            ]);
        }

        $columnMap = $this->detectHeaderMap($rows[0]);
        $startIndex = $columnMap === null ? 0 : 1;
        $result = new UserBulkImportResult;

        for ($index = $startIndex; $index < count($rows); $index++) {
            $rowNumber = $index + 1;
            $parsed = $this->parseRow($rows[$index], $columnMap);

            if ($parsed === null) {
                continue;
            }

            [$name, $mobileRaw, $roleLabel] = $parsed;

            if ($name === '' && $mobileRaw === '' && $roleLabel === '') {
                continue;
            }

            if ($name === '') {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'نام و نام خانوادگی خالی است.'];

                continue;
            }

            $mobile = MobileNumber::normalize($mobileRaw);

            if ($mobile === null) {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'شماره موبایل معتبر نیست: '.$mobileRaw];

                continue;
            }

            $roleName = RoleLabelResolver::resolve($roleLabel);

            if ($roleName === null) {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'نقش یا دسترسی ناشناخته است: '.$roleLabel];

                continue;
            }

            if (in_array($roleName, self::BLOCKED_ROLE_NAMES, true)) {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'ایجاد نقش '.$roleLabel.' از ورود گروهی مجاز نیست.'];

                continue;
            }

            if (! Role::query()->where('name', $roleName)->exists()) {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'نقش «'.$roleLabel.'» در سامانه تعریف نشده است. ابتدا seeder نقش‌ها را اجرا کنید.'];

                continue;
            }

            if (User::query()->where('mobile', $mobile)->exists()) {
                $result->skipped[] = ['row' => $rowNumber, 'message' => 'کاربر با موبایل '.$mobile.' از قبل وجود دارد.'];

                continue;
            }

            $email = $this->uniqueStaffEmail($mobile);

            try {
                DB::transaction(function () use ($name, $email, $mobile, $password, $active, $roleName, $actor): void {
                    $user = User::query()->create([
                        'name' => $name,
                        'email' => $email,
                        'mobile' => $mobile,
                        'password' => Hash::make($password),
                        'is_active' => $active,
                    ]);

                    $user->syncRoles([$roleName]);

                    $this->auditLog->record('user.created', $user, 'success', [
                        'roles' => [$roleName],
                        'is_active' => $active,
                        'bulk_import' => true,
                    ], $actor);
                });
            } catch (\Throwable $exception) {
                $result->failures[] = ['row' => $rowNumber, 'message' => $exception->getMessage()];

                continue;
            }

            $result->created++;
        }

        return $result;
    }

    /**
     * @param  list<string>  $headerRow
     * @return array{name: int, mobile: int, role: int}|null
     */
    private function detectHeaderMap(array $headerRow): ?array
    {
        $normalized = array_map(fn (string $cell): string => $this->normalizeHeader($cell), $headerRow);

        $nameAliases = ['نام', 'نام و نام خانوادگی', 'نام خانوادگی', 'نام كامل', 'نام کامل'];
        $mobileAliases = ['موبایل', 'شماره موبایل', 'شماره تماس', 'تلفن'];
        $roleAliases = ['دسترسی', 'نقش', 'سطح دسترسی', 'نقش کاربری'];

        $map = [];

        foreach ($normalized as $index => $header) {
            if (in_array($header, $nameAliases, true)) {
                $map['name'] = $index;
            }

            if (in_array($header, $mobileAliases, true)) {
                $map['mobile'] = $index;
            }

            if (in_array($header, $roleAliases, true)) {
                $map['role'] = $index;
            }
        }

        if (isset($map['name'], $map['mobile'], $map['role'])) {
            return $map;
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        $value = MobileNumber::toEnglishDigits($value);

        return mb_strtolower(str_replace('‌', ' ', $value));
    }

    /**
     * @param  list<string>  $cells
     * @param  array{name: int, mobile: int, role: int}|null  $columnMap
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function parseRow(array $cells, ?array $columnMap): ?array
    {
        $cells = array_map(fn ($cell): string => trim((string) ($cell ?? '')), $cells);

        if ($columnMap !== null) {
            return [
                $cells[$columnMap['name']] ?? '',
                $cells[$columnMap['mobile']] ?? '',
                $cells[$columnMap['role']] ?? '',
            ];
        }

        $mobileIndex = null;
        $roleIndex = null;

        foreach ($cells as $index => $cell) {
            if ($cell === '') {
                continue;
            }

            if ($mobileIndex === null && MobileNumber::normalize($cell) !== null) {
                $mobileIndex = $index;

                continue;
            }

            if ($roleIndex === null && RoleLabelResolver::resolve($cell) !== null) {
                $roleIndex = $index;
            }
        }

        if ($mobileIndex === null) {
            return ['', '', ''];
        }

        $mobile = $cells[$mobileIndex];
        $role = $roleIndex !== null ? $cells[$roleIndex] : '';

        $nameParts = [];

        foreach ($cells as $index => $cell) {
            if ($index === $mobileIndex || $index === $roleIndex || $cell === '') {
                continue;
            }

            $nameParts[] = $cell;
        }

        $name = trim(implode(' ', $nameParts));

        if ($name === '' && count($cells) >= 3) {
            $indexes = array_keys(array_filter($cells, fn (string $c): bool => $c !== ''));
            foreach ($indexes as $index) {
                if ($index !== $mobileIndex && $index !== $roleIndex) {
                    $name = $cells[$index];
                    break;
                }
            }
        }

        return [$name, $mobile, $role];
    }

    private function uniqueStaffEmail(string $mobile): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'staff.local';
        $digits = preg_replace('/\D+/', '', $mobile) ?? $mobile;
        $base = 'import+'.$digits.'@'.$host;

        if (! User::query()->where('email', $base)->exists()) {
            return $base;
        }

        return 'import+'.$digits.'+'.Str::lower(Str::random(6)).'@'.$host;
    }
}