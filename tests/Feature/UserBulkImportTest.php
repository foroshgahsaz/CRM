<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserBulkImportService;
use Tests\CrmTestCase;

class UserBulkImportTest extends CrmTestCase
{
    public function test_imports_users_from_csv_with_persian_mobile_and_representative_role(): void
    {
        $admin = $this->staff('super_admin');
        $path = $this->writeCsv(<<<'CSV'
نام و نام خانوادگی,شماره موبایل,دسترسی
محمد خلیلی,۰۹۱۴۱۴۹۵۰۰۱,نماینده
مجید حیدرزاده,09151018951,نماینده
CSV);

        $result = app(UserBulkImportService::class)->import(
            $path,
            'SecurePass1!',
            true,
            $admin,
        );

        $this->assertSame(2, $result->created);
        $this->assertDatabaseHas('users', ['mobile' => '09141495001', 'name' => 'محمد خلیلی']);
        $this->assertDatabaseHas('users', ['mobile' => '09151018951', 'name' => 'مجید حیدرزاده']);

        $user = User::query()->where('mobile', '09141495001')->firstOrFail();
        $this->assertTrue($user->hasRole('representative'));
    }

    public function test_import_uses_default_role_when_access_column_missing(): void
    {
        $admin = $this->staff('super_admin');

        $path = $this->writeCsv(<<<'CSV'
نام و نام خانوادگی,شماره موبایل
علی رضایی,09143216547
CSV);

        $result = app(UserBulkImportService::class)->import($path, 'SecurePass1!', true, $admin, 'representative');

        $this->assertSame(1, $result->created);
        $this->assertTrue(User::query()->where('mobile', '09143216547')->firstOrFail()->hasRole('representative'));
    }

    public function test_import_skips_duplicate_mobile(): void
    {
        $admin = $this->staff('super_admin');
        User::factory()->create(['mobile' => '09141112233', 'email' => 'dup@example.com']);

        $path = $this->writeCsv(<<<'CSV'
نام و نام خانوادگی,شماره موبایل,دسترسی
تکراری,09141112233,نماینده
CSV);

        $result = app(UserBulkImportService::class)->import($path, 'SecurePass1!', true, $admin);

        $this->assertSame(0, $result->created);
        $this->assertCount(1, $result->skipped);
    }

    public function test_bulk_import_page_requires_users_manage(): void
    {
        $expert = $this->staff('expert');
        $this->actingAs($expert)->get(\App\Filament\Pages\BulkImportUsers::getUrl())->assertForbidden();

        $admin = $this->staff('super_admin');
        $this->actingAs($admin)->get(\App\Filament\Pages\BulkImportUsers::getUrl())->assertOk();
    }

    private function writeCsv(string $contents): string
    {
        $path = storage_path('app/testing-bulk-'.uniqid().'.csv');
        file_put_contents($path, "\xEF\xBB\xBF".$contents);

        return $path;
    }
}