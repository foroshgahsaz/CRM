<?php

namespace App\Services;

use App\Enums\CrmBackupScope;
use App\Models\User;
use App\Support\PrivateStorageBootstrap;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class CrmBackupService
{
    /**
     * @return array{filename: string, path: string, bytes: int, triggered_by: string, scope: string}
     */
    public function run(
        string $triggeredBy = 'schedule',
        ?User $actor = null,
        CrmBackupScope $scope = CrmBackupScope::Full,
    ): array {
        PrivateStorageBootstrap::assertWebWritable($this->root(), 'بکاپ');
        $this->ensureRootExists();

        $filename = $this->buildFilename($triggeredBy, $scope);
        $path = $this->root().DIRECTORY_SEPARATOR.$filename;
        $tempDir = storage_path('framework/backup-work/'.Str::uuid());

        File::makeDirectory($tempDir, 0775, true, true);

        try {
            if ($scope === CrmBackupScope::Full || $scope === CrmBackupScope::Database) {
                $this->dumpDatabase($tempDir);
            }

            if ($scope === CrmBackupScope::Full || $scope === CrmBackupScope::Files) {
                $this->copyPrivateTree($tempDir);
            }

            $this->writeManifest($tempDir, $triggeredBy, $actor, $scope);

            if (! $this->zipDirectory($tempDir, $path)) {
                throw new RuntimeException('ساخت فایل فشردهٔ بکاپ ناموفق بود.');
            }
        } finally {
            File::deleteDirectory($tempDir);
        }

        $bytes = (int) filesize($path);
        $this->applyRetention();

        return [
            'filename' => $filename,
            'path' => $path,
            'bytes' => $bytes,
            'triggered_by' => $triggeredBy,
            'scope' => $scope->value,
        ];
    }

    /**
     * @return list<array{filename: string, path: string, bytes: int, created_at: string, scope: string}>
     */
    public function list(): array
    {
        $this->ensureRootExists();

        $files = collect(File::files($this->root()))
            ->filter(fn (\SplFileInfo $file): bool => str_starts_with($file->getFilename(), 'crm-backup-') && str_ends_with($file->getFilename(), '.zip'))
            ->sortByDesc(fn (\SplFileInfo $file): int => $file->getMTime());

        return $files->map(fn (\SplFileInfo $file): array => [
            'filename' => $file->getFilename(),
            'path' => $file->getRealPath() ?: $file->getPathname(),
            'bytes' => $file->getSize(),
            'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            'scope' => $this->scopeFromFilename($file->getFilename()),
        ])->values()->all();
    }

    public function resolveDownloadPath(string $filename): string
    {
        if (! preg_match('/^crm-backup-[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}-(auto|manual)-(full|database|files)\.zip$/', $filename)
            && ! preg_match('/^crm-backup-[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}-(auto|manual)\.zip$/', $filename)) {
            throw new RuntimeException('نام فایل بکاپ معتبر نیست.');
        }

        $path = $this->root().DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            throw new RuntimeException('فایل بکاپ یافت نشد.');
        }

        return $path;
    }

    public function root(): string
    {
        return (string) config('crm.backup.root');
    }

    public function retentionCount(): int
    {
        return max(1, (int) config('crm.backup.retention', 2));
    }

    private function buildFilename(string $triggeredBy, CrmBackupScope $scope): string
    {
        $suffix = $triggeredBy === 'manual' ? 'manual' : 'auto';

        return 'crm-backup-'.now()->format('Y-m-d_His').'-'.$suffix.'-'.$scope->value.'.zip';
    }

    private function scopeFromFilename(string $filename): string
    {
        if (preg_match('/-(full|database|files)\.zip$/', $filename, $matches)) {
            return $matches[1];
        }

        return 'full';
    }

    private function ensureRootExists(): void
    {
        PrivateStorageBootstrap::ensureRootExists();
    }

    private function dumpDatabase(string $tempDir): void
    {
        $connection = (string) config('database.default');
        $target = $tempDir.DIRECTORY_SEPARATOR.'database.sql';

        if ($connection === 'sqlite') {
            $database = (string) config('database.connections.sqlite.database');

            if (! is_file($database)) {
                throw new RuntimeException('فایل SQLite یافت نشد.');
            }

            if (! copy($database, $target)) {
                throw new RuntimeException('کپی فایل SQLite برای بکاپ ناموفق بود.');
            }

            return;
        }

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('نوع پایگاه داده برای بکاپ پشتیبانی نمی‌شود: '.$connection);
        }

        if ($this->mysqldumpAvailable()) {
            $this->dumpDatabaseWithMysqldump($target, $connection);
        } else {
            app(PhpMysqlDatabaseExporter::class)->exportToFile($target, $connection);
        }
    }

    private function mysqldumpAvailable(): bool
    {
        $process = Process::run(['sh', '-c', 'command -v mysqldump']);

        return $process->successful();
    }

    private function dumpDatabaseWithMysqldump(string $target, string $connection): void
    {
        $config = config('database.connections.'.$connection);
        $password = (string) ($config['password'] ?? '');

        $process = Process::timeout(600)->env($password !== '' ? ['MYSQL_PWD' => $password] : [])->run([
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? '3306'),
            '--user='.(string) ($config['username'] ?? 'root'),
            (string) ($config['database'] ?? ''),
        ]);

        if (! $process->successful()) {
            throw new RuntimeException('mysqldump ناموفق بود: '.$process->errorOutput());
        }

        file_put_contents($target, $process->output());
    }

    private function copyPrivateTree(string $tempDir): void
    {
        $privateRoot = (string) config('filesystems.disks.local.root');
        $dest = $tempDir.DIRECTORY_SEPARATOR.'storage-private';

        if (! File::isDirectory($privateRoot)) {
            File::makeDirectory($privateRoot, 0755, true, true);
        }

        File::copyDirectory($privateRoot, $dest);

        $legacyPrivate = $privateRoot.DIRECTORY_SEPARATOR.'private';

        if (File::isDirectory($legacyPrivate)) {
            File::copyDirectory($legacyPrivate, $dest.DIRECTORY_SEPARATOR.'legacy-private');
        }

        $publicRoot = storage_path('app/public');

        if (config('crm.backup.include_public', true) && File::isDirectory($publicRoot)) {
            File::copyDirectory($publicRoot, $tempDir.DIRECTORY_SEPARATOR.'storage-public');
        }
    }

    private function writeManifest(string $tempDir, string $triggeredBy, ?User $actor, CrmBackupScope $scope): void
    {
        $manifest = [
            'app' => config('app.name'),
            'created_at' => now()->toIso8601String(),
            'triggered_by' => $triggeredBy,
            'scope' => $scope->value,
            'actor_id' => $actor?->id,
            'actor_email' => $actor?->email,
            'db_connection' => config('database.default'),
            'db_export' => $this->mysqldumpAvailable() ? 'mysqldump' : 'php',
            'private_root' => config('filesystems.disks.local.root'),
        ];

        file_put_contents(
            $tempDir.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function zipDirectory(string $sourceDir, string $zipPath): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $sourceDir = realpath($sourceDir) ?: $sourceDir;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            $filePath = $file->getRealPath();

            if ($filePath === false) {
                continue;
            }

            $relative = substr($filePath, strlen($sourceDir) + 1);
            $relative = str_replace('\\', '/', $relative);

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($filePath, $relative);
            }
        }

        $zip->close();

        return is_file($zipPath);
    }

    private function applyRetention(): void
    {
        $keep = $this->retentionCount();

        collect($this->list())
            ->groupBy('scope')
            ->each(function ($group) use ($keep): void {
                foreach ($group->slice($keep) as $item) {
                    if (is_file($item['path'])) {
                        unlink($item['path']);
                    }
                }
            });
    }
}