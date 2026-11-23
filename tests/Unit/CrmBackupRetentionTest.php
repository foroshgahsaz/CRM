<?php

namespace Tests\Unit;

use App\Services\CrmBackupService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CrmBackupRetentionTest extends TestCase
{
    public function test_retention_keeps_only_newest_archives(): void
    {
        $root = storage_path('framework/testing/backups-'.uniqid());
        config(['crm.backup.root' => $root, 'crm.backup.retention' => 2]);

        File::makeDirectory($root, 0755, true, true);

        foreach ([3, 2, 1] as $index) {
            $path = $root.'/crm-backup-2026-01-0'.$index.'_120000-auto.zip';
            file_put_contents($path, 'test-'.$index);
            touch($path, now()->subDays(3 - $index)->timestamp);
        }

        $service = app(CrmBackupService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('applyRetention');
        $method->invoke($service);

        $remaining = collect(File::files($root))->map->getFilename()->values()->all();

        $this->assertCount(2, $remaining);
        $this->assertContains('crm-backup-2026-01-03_120000-auto.zip', $remaining);
        $this->assertContains('crm-backup-2026-01-02_120000-auto.zip', $remaining);

        File::deleteDirectory($root);
    }
}