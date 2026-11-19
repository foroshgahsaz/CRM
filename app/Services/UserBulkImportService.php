<?php

namespace App\Services;

use App\Models\User;
use App\Support\MobileNumber;
use App\Support\RoleLabelResolver;
use App\Support\RoleProvisioner;
use Illuminate\Database\UniqueConstraintViolationException;
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

    public function import(
        string $absolutePath,
        string $password,
        bool $active,
        User $actor,
        string $defaultRoleName = 'representative',
    ): UserBulkImportResult {
        $rows = $this->reader->rows($absolutePath);

        if ($rows === []) {
            return new UserBulkImportResult(failures: [
                ['row' => 0, 'message' => 'فایل خالی است.'],
            ]);
        }

        [$columnMap, $startIndex] = $this->resolveColumnLayout($rows);
        if ($startIndex === 0 && $columnMap !== null && $this->rowLooksLikeHeader($rows[0] ?? [])) {
            $startIndex = 1;
        }
        $result = new UserBulkImportResult;

        $defaultRoleName = RoleLabelResolver::resolve($defaultRoleName) ?? $defaultRoleName;

        if (! in_array($defaultRoleName, self::BLOCKED_ROLE_NAMES, true)) {
            RoleProvisioner::ensure($defaultRoleName);
        }

        /** @var array<string, true> $seenMobiles */
        $seenMobiles = [];

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

            if ($roleName === null && $roleLabel === '') {
                $roleName = $defaultRoleName;
            }

            if ($roleName === null) {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'نقش یا دسترسی ناشناخته است: '.$roleLabel];

                continue;
            }

            if (in_array($roleName, self::BLOCKED_ROLE_NAMES, true)) {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'ایجاد نقش '.$roleLabel.' از ورود گروهی مجاز نیست.'];

                continue;
            }

            RoleProvisioner::ensure($roleName);

            if (! Role::query()->where('name', $roleName)->exists()) {
                $result->failures[] = ['row' => $rowNumber, 'message' => 'نقش «'.$roleLabel.'" در سامانه تعریف نشده است.'];

                continue;
            }

            if (isset($seenMobiles[$mobile])) {
                $result->skipped[] = ['row' => $rowNumber, 'message' => 'موبایل '.$mobile.' در همین فایل تکراری است.'];

                continue;
            }

            if (User::withTrashed()->where('mobile', $mobile)->exists()) {
                $result->skipped[] = ['row' => $rowNumber, 'message' => 'کاربر با موبایل '.$mobile.' از قبل وجود دارد.'];

                continue;
            }

            $seenMobiles[$mobile] = true;

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
            } catch (UniqueConstraintViolationException) {
                $result->skipped[] = ['row' => $rowNumber, 'message' => 'موبایل یا ایمیل '.$mobile.' تکراری است و قبلاً ثبت شده.'];

                continue;
            } catch (\Throwable $exception) {
                $result->failures[] = ['row' => $rowNumber, 'message' => $exception->getMessage()];

                continue;
            }

            $result->created++;
        }

        return $result;
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array{0: array{name: int, mobile: int, role: int}|null, 1: int}
     */
    private function resolveColumnLayout(array $rows): array
    {
        $headerMap = $this->detectHeaderMap($rows[0] ?? []);

        if ($headerMap !== null) {
            return [$headerMap, 1];
        }

        return [$this->inferColumnMap($rows), 0];
    }

    /**
     * @param  list<string>  $row
     */
    private function rowLooksLikeHeader(array $row): bool
    {
        return $this->detectHeaderMap($row) !== null;
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array{name: int, mobile: int, role: int}|null
     */
    private function inferColumnMap(array $rows): ?array
    {
        $sample = array_slice($rows, 0, min(20, count($rows)));
        $width = 0;

        foreach ($sample as $row) {
            $width = max($width, count($row));
        }

        if ($width === 0) {
            return null;
        }

        $mobileScores = array_fill(0, $width, 0);
        $roleScores = array_fill(0, $width, 0);
        $textScores = array_fill(0, $width, 0);

        foreach ($sample as $row) {
            foreach ($row as $index => $cell) {
                $cell = trim((string) $cell);
                if ($cell === '') {
                    continue;
                }

                if (MobileNumber::normalize($cell) !== null) {
                    $mobileScores[$index]++;
                } elseif (RoleLabelResolver::resolve($cell) !== null) {
                    $roleScores[$index]++;
                } elseif (mb_strlen($cell) >= 2 && ! preg_match('/^\d+$/', $cell)) {
                    $textScores[$index]++;
                }
            }
        }

        $mobileIndex = $this->indexWithMaxScore($mobileScores);

        if ($mobileIndex === null) {
            return null;
        }

        $roleIndex = $this->indexWithMaxScore($roleScores, $mobileIndex);
        $nameIndex = $this->indexWithMaxScore($textScores, $mobileIndex, $roleIndex);

        if ($nameIndex === null) {
            foreach ($textScores as $index => $score) {
                if ($index !== $mobileIndex && $index !== $roleIndex && $score > 0) {
                    $nameIndex = $index;
                    break;
                }
            }
        }

        if ($nameIndex === null) {
            return null;
        }

        $map = [
            'name' => $nameIndex,
            'mobile' => $mobileIndex,
        ];

        if ($roleIndex !== null) {
            $map['role'] = $roleIndex;
        }

        return $map;
    }

    /**
     * @param  list<int>  $scores
     */
    private function indexWithMaxScore(array $scores, ?int ...$exclude): ?int
    {
        $exclude = array_flip(array_filter($exclude, fn (?int $v): bool => $v !== null));

        $bestIndex = null;
        $bestScore = 0;

        foreach ($scores as $index => $score) {
            if (isset($exclude[$index]) || $score <= 0) {
                continue;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  list<string>  $headerRow
     * @return array{name: int, mobile: int, role: int}|null
     */
    private function detectHeaderMap(array $headerRow): ?array
    {
        $normalized = array_map(fn (string $cell): string => $this->normalizeHeader($cell), $headerRow);

        $nameAliases = ['نام', 'نام و نام خانوادگی', 'نام خانوادگی', 'نام كامل', 'نام کامل', 'name'];
        $mobileAliases = ['موبایل', 'شماره موبایل', 'شماره تماس', 'تلفن', 'mobile', 'phone'];
        $roleAliases = ['دسترسی', 'نقش', 'سطح دسترسی', 'نقش کاربری', 'access', 'role'];

        $map = [];

        foreach ($normalized as $index => $header) {
            if ($this->matchesAny($header, $nameAliases)) {
                $map['name'] = $index;
            }

            if ($this->matchesAny($header, $mobileAliases)) {
                $map['mobile'] = $index;
            }

            if ($this->matchesAny($header, $roleAliases)) {
                $map['role'] = $index;
            }
        }

        if (isset($map['name'], $map['mobile'])) {
            $map['role'] ??= $this->guessRoleColumn($headerRow, $map);

            return $map;
        }

        return null;
    }

    /**
     * @param  list<string>  $headerRow
     * @param  array{name: int, mobile: int, role?: int}  $map
     */
    private function guessRoleColumn(array $headerRow, array $map): int
    {
        $used = array_values($map);

        for ($i = count($headerRow) - 1; $i >= 0; $i--) {
            if (! in_array($i, $used, true)) {
                return $i;
            }
        }

        return max($map['name'], $map['mobile']) + 1;
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        if (in_array($haystack, $needles, true)) {
            return true;
        }

        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHeader(string $value): string
    {
        $value = RoleLabelResolver::normalizeLabel($value);

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
            $name = $cells[$columnMap['name']] ?? '';
            $mobile = $cells[$columnMap['mobile']] ?? '';
            $role = isset($columnMap['role']) ? ($cells[$columnMap['role']] ?? '') : '';

            if ($role === '') {
                $skip = [$columnMap['name'], $columnMap['mobile']];
                if (isset($columnMap['role'])) {
                    $skip[] = $columnMap['role'];
                }

                $role = $this->findRoleInCells($cells, $skip);
            }

            return [$name, $mobile, $role];
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
        $role = $roleIndex !== null ? $cells[$roleIndex] : $this->findRoleInCells($cells, [$mobileIndex]);

        $nameParts = [];

        foreach ($cells as $index => $cell) {
            if ($index === $mobileIndex || $index === $roleIndex || $cell === '') {
                continue;
            }

            $nameParts[] = $cell;
        }

        $name = trim(implode(' ', $nameParts));

        if ($name === '') {
            foreach ($cells as $index => $cell) {
                if ($index !== $mobileIndex && $index !== $roleIndex && $cell !== '') {
                    $name = $cell;
                    break;
                }
            }
        }

        return [$name, $mobile, $role];
    }

    /**
     * @param  list<string>  $cells
     * @param  list<int>  $skipIndexes
     */
    private function findRoleInCells(array $cells, array $skipIndexes): string
    {
        $skip = array_flip($skipIndexes);

        foreach ($cells as $index => $cell) {
            if (isset($skip[$index]) || $cell === '') {
                continue;
            }

            if (RoleLabelResolver::resolve($cell) !== null) {
                return $cell;
            }
        }

        return '';
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