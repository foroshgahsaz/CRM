<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class CrmBackupService
{
    /**
     * @return array{filename: string, path: string, bytes: int, triggered_by: string}
     */
    public function run(string $triggeredBy = 'schedule', ?User $actor = null): array
    {
        $this->ensureRootExists();

        $filename = $this->buildFilename($triggeredBy);
        $path = $this->root().DIRECTORY_SEPARATOR.$filename;
        $tempDir = $this->root().DIRECTORY_SEPARATOR.'.work-'.Str::uuid();

        File::makeDirectory($tempDir, 0755, true, true);

        try {
            $this->dumpDatabase($tempDir);
            $this->copyPrivateTree($tempDir);
            $this->writeManifest($tempDir, $triggeredBy, $actor);

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
        ];
    }

    /**
     * @return list<array{filename: string, path: string, bytes: int, created_at: string}>
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
        ])->values()->all();
    }

    public function resolveDownloadPath(string $filename): string
    {
        if (! preg_match('/^crm-backup-[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}-(auto|manual)\.zip$/', $filename)) {
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

    private function buildFilename(string $triggeredBy): string
    {
        $suffix = $triggeredBy === 'manual' ? 'manual' : 'auto';

        return 'crm-backup-'.now()->format('Y-m-d_His').'-'.$suffix.'.zip';
    }

    private function ensureRootExists(): void
    {
        $root = $this->root();

        if (! File::isDirectory($root)) {
            File::makeDirectory($root, 0755, true, true);
        }
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
            throw new RuntimeException('mysqldump ناموفق بود. آیا mysqldump روی سرور نصب است؟ '.$process->errorOutput());
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

        $publicRoot = storage_path('app/public');

        if (config('crm.backup.include_public', true) && File::isDirectory($publicRoot)) {
            File::copyDirectory($publicRoot, $tempDir.DIRECTORY_SEPARATOR.'storage-public');
        }
    }

    private function writeManifest(string $tempDir, string $triggeredBy, ?User $actor): void
    {
        $manifest = [
            'app' => config('app.name'),
            'created_at' => now()->toIso8601String(),
            'triggered_by' => $triggeredBy,
            'actor_id' => $actor?->id,
            'actor_email' => $actor?->email,
            'db_connection' => config('database.default'),
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
        $items = collect($this->list())->values();

        foreach ($items->slice($keep) as $item) {
            if (is_file($item['path'])) {
                unlink($item['path']);
            }
        }
    }
}