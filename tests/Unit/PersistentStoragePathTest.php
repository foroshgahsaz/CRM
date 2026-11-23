<?php

namespace Tests\Unit;

use App\Support\PersistentStoragePath;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersistentStoragePathTest extends TestCase
{
    #[DataProvider('privateRootProvider')]
    public function test_private_root_prefers_explicit_env_then_storage_app(array $env, string $storageApp, string $expected): void
    {
        $this->assertSame($expected, PersistentStoragePath::privateRootFrom($env, $storageApp));
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string, 2: string}>
     */
    public static function privateRootProvider(): array
    {
        return [
            'liara default mount' => [
                [],
                '/app/storage/app',
                '/app/storage/app',
            ],
            'explicit override' => [
                ['CRM_STORAGE_PRIVATE_ROOT' => '/mnt/disk'],
                '/app/storage/app',
                '/mnt/disk',
            ],
            'trims trailing slash' => [
                ['CRM_STORAGE_PRIVATE_ROOT' => '/app/storage/app/'],
                '/tmp/storage/app',
                '/app/storage/app',
            ],
            'does not nest private' => [
                [],
                '/app/storage/app',
                '/app/storage/app',
            ],
        ];
    }

    public function test_backup_root_sits_on_the_same_disk(): void
    {
        $this->assertSame(
            '/app/storage/app/backups/crm',
            PersistentStoragePath::backupRootFrom([], '/app/storage/app'),
        );
    }

    public function test_existing_file_path_finds_legacy_private_copy(): void
    {
        $root = sys_get_temp_dir().'/crm-disk-'.uniqid();
        $legacy = $root.'/private/attachments/9/file.pdf';
        mkdir(dirname($legacy), 0777, true);
        file_put_contents($legacy, 'ok');

        $this->assertSame(
            $legacy,
            PersistentStoragePath::existingFilePath($root, 'attachments/9/file.pdf'),
        );
        $this->assertNull(PersistentStoragePath::existingFilePath($root, '../.env'));
    }
}
