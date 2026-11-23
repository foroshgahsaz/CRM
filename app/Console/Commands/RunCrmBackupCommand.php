<?php

namespace App\Console\Commands;

use App\Enums\CrmBackupScope;
use App\Services\AuditLogService;
use App\Services\CrmBackupService;
use Illuminate\Console\Command;

class RunCrmBackupCommand extends Command
{
    protected $signature = 'crm:backup {--manual : بکاپ دستی (برچسب manual)} {--database : فقط پایگاه داده} {--files : فقط فایل‌ها}';

    protected $description = 'بکاپ فشرده از پایگاه داده و/یا فایل‌های private (و public) با نگه‌داری محدود';

    public function handle(CrmBackupService $backups, AuditLogService $audit): int
    {
        $triggeredBy = $this->option('manual') ? 'manual' : 'schedule';
        $scope = $this->resolveScope();

        try {
            $result = $backups->run($triggeredBy, null, $scope);

            $audit->record('backup.created', null, 'success', [
                'filename' => $result['filename'],
                'bytes' => $result['bytes'],
                'triggered_by' => $triggeredBy,
            ]);

            $this->info('بکاپ ساخته شد: '.$result['filename'].' ('.number_format($result['bytes'] / 1024, 1).' KiB)');

            if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
                $this->warn('بکاپ با root ساخته شد. برای بکاپ از پنل: chown -R www-data:www-data storage/app');
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $audit->record('backup.failed', null, 'failure', [
                'message' => $exception->getMessage(),
                'triggered_by' => $triggeredBy,
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveScope(): CrmBackupScope
    {
        $database = (bool) $this->option('database');
        $files = (bool) $this->option('files');

        if ($database && $files) {
            return CrmBackupScope::Full;
        }

        if ($database) {
            return CrmBackupScope::Database;
        }

        if ($files) {
            return CrmBackupScope::Files;
        }

        return CrmBackupScope::Full;
    }
}